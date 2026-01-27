<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Public_leads_api extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('public_leads_api/public_leads_api_model');
    }

    public function index()
    {
        if ($this->input->post('action') === 'generate') {
            $label = $this->input->post('label');
            $token = $this->public_leads_api_model->create_key($label);
            set_alert('success', 'New API key created: ' . $token);
            redirect(admin_url('public_leads_api'));
        }

        if ($this->input->post('action') === 'settings') {
            $enabled    = $this->input->post('enabled') ? 1 : 0;
            $rateLimit  = (int) $this->input->post('rate_limit');
            $rateLimit  = $rateLimit < 0 ? 0 : $rateLimit;

            update_option('public_leads_api_enabled', $enabled);
            update_option('public_leads_api_rate_limit_per_minute', $rateLimit);

            set_alert('success', 'Settings updated');
            redirect(admin_url('public_leads_api'));
        }

        $data['title']      = 'Public Leads API';
        $data['keys']       = $this->public_leads_api_model->get_keys();
        $data['logs']       = $this->public_leads_api_model->recent_logs(25);
        $data['enabled']    = (int) get_option('public_leads_api_enabled', 1);
        $data['rate_limit'] = (int) get_option('public_leads_api_rate_limit_per_minute', 60);

        $this->load->view(PUBLIC_LEADS_API_MODULE . '/manage', $data);
    }

    public function revoke($id)
    {
        if ($this->public_leads_api_model->deactivate_key((int) $id)) {
            set_alert('success', 'API key revoked');
        } else {
            set_alert('warning', 'Key not found or already inactive');
        }

        redirect(admin_url('public_leads_api'));
    }

    public function delete($id)
    {
        if ($this->public_leads_api_model->delete_key((int) $id)) {
            set_alert('success', 'API key deleted');
        } else {
            set_alert('warning', 'Unable to delete key');
        }

        redirect(admin_url('public_leads_api'));
    }
}
