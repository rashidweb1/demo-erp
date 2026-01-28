<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Public_api extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('public_leads_api/public_leads_api_model');
        $this->load->model('leads_model');
        $this->output->set_content_type('application/json');
    }

    public function store()
    {
        $enabled = (int) get_option('public_leads_api_enabled', 1);
        if ($enabled !== 1) {
            return $this->respond(503, [
                'status'  => false,
                'message' => 'Public API is disabled',
            ]);
        }

        $token = $this->read_token();
        if (!$token) {
            return $this->respond(401, [
                'status'  => false,
                'message' => 'Missing API key',
            ]);
        }

        $apiKey = $this->public_leads_api_model->get_active_key_by_token($token);
        if (!$apiKey) {
            $this->public_leads_api_model->log_request([
                'api_key_id' => null,
                'status'     => 'denied',
                'message'    => 'Invalid API key',
                'payload'    => [],
                'ip'         => $this->input->ip_address(),
                'code'       => 401,
            ]);

            return $this->respond(401, [
                'status'  => false,
                'message' => 'Invalid API Key',
            ]);
        }

        $rateLimit = (int) get_option('public_leads_api_rate_limit_per_minute', 60);
        if ($this->public_leads_api_model->is_rate_limited((int) $apiKey->id, $rateLimit)) {
            $this->public_leads_api_model->log_request([
                'api_key_id' => $apiKey->id,
                'status'     => 'rate_limited',
                'message'    => 'Rate limit exceeded',
                'payload'    => [],
                'ip'         => $this->input->ip_address(),
                'code'       => 429,
            ]);

            return $this->respond(429, [
                'status'  => false,
                'message' => 'Rate limit exceeded',
            ]);
        }

        $payload = public_leads_api_read_payload();
        if (empty($payload)) {
            return $this->respond(400, [
                'status'  => false,
                'message' => 'Empty payload',
            ]);
        }

        // Require lead name; don't auto-fill with defaults
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $this->public_leads_api_model->log_request([
                'api_key_id' => $apiKey->id,
                'status'     => 'error',
                'message'    => 'Name is required',
                'payload'    => $payload,
                'ip'         => $this->input->ip_address(),
                'code'       => 422,
            ]);

            return $this->respond(422, [
                'status'  => false,
                'message' => 'The field \"name\" is required',
            ]);
        }

        try {
            $leadData = $this->public_leads_api_model->prepare_lead($payload);
            $leadId   = $this->leads_model->add($leadData);

            if (!$leadId) {
                throw new Exception('Lead creation failed');
            }

            $this->public_leads_api_model->touch_key_usage((int) $apiKey->id);
            $this->public_leads_api_model->log_request([
                'api_key_id' => $apiKey->id,
                'lead_id'    => $leadId,
                'status'     => 'success',
                'message'    => 'Lead created',
                'payload'    => $payload,
                'ip'         => $this->input->ip_address(),
                'code'       => 200,
            ]);

            return $this->respond(200, [
                'status'  => true,
                'message' => 'Lead created successfully',
                'lead_id' => $leadId,
            ]);
        } catch (Throwable $e) {
            $this->public_leads_api_model->log_request([
                'api_key_id' => $apiKey->id,
                'status'     => 'error',
                'message'    => $e->getMessage(),
                'payload'    => $payload,
                'ip'         => $this->input->ip_address(),
                'code'       => 500,
            ]);

            return $this->respond(500, [
                'status'  => false,
                'message' => 'Unable to create lead',
            ]);
        }
    }

    private function respond(int $code, array $body)
    {
        return $this->output
            ->set_status_header($code)
            ->set_output(json_encode($body));
    }

    private function read_token(): ?string
    {
        $headerKey = $this->input->get_request_header('X-API-KEY');
        if ($headerKey) {
            return trim($headerKey);
        }

        $auth = $this->input->get_request_header('Authorization');
        if ($auth && stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }

        return null;
    }
}
