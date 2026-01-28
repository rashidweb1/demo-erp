<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Public_leads_api_model extends App_Model
{
    private $keysTable;
    private $logsTable;

    public function __construct()
    {
        parent::__construct();
        $this->keysTable = db_prefix() . 'pla_api_keys';
        $this->logsTable = db_prefix() . 'pla_api_logs';
    }

    public function create_key(string $label): string
    {
        $token = bin2hex(random_bytes(24));
        $now   = date('Y-m-d H:i:s');

        $this->db->insert($this->keysTable, [
            'label'        => $label ?: 'Untitled',
            'api_key'      => $token,
            'active'       => 1,
            'usage_count'  => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        return $token;
    }

    public function get_keys(): array
    {
        return $this->db->order_by('created_at', 'desc')->get($this->keysTable)->result_array();
    }

    public function get_key(int $id)
    {
        return $this->db->where('id', $id)->get($this->keysTable)->row();
    }

    public function deactivate_key(int $id): bool
    {
        $this->db->where('id', $id)->update($this->keysTable, ['active' => 0]);

        return $this->db->affected_rows() > 0;
    }

    public function delete_key(int $id): bool
    {
        $this->db->where('id', $id)->delete($this->keysTable);

        return $this->db->affected_rows() > 0;
    }

    public function get_active_key_by_token(string $token)
    {
        return $this->db->where('api_key', $token)
                        ->where('active', 1)
                        ->get($this->keysTable)
                        ->row();
    }

    public function touch_key_usage(int $id): void
    {
        $this->db->set('usage_count', 'usage_count+1', false);
        $this->db->set('last_used_at', date('Y-m-d H:i:s'));
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('id', $id)->update($this->keysTable);
    }

    public function is_rate_limited(int $apiKeyId, int $limitPerWindow, int $windowMinutes = 1): bool
    {
        if ($limitPerWindow <= 0) {
            return false;
        }

        $minutes   = $windowMinutes <= 0 ? 1 : $windowMinutes;
        $threshold = date('Y-m-d H:i:s', time() - 60 * $minutes);

        $count = $this->db->where('api_key_id', $apiKeyId)
                          ->where('created_at >=', $threshold)
                          ->count_all_results($this->logsTable);

        return $count >= $limitPerWindow;
    }

    public function log_request(array $data): void
    {
        $payload = $data['payload'] ?? [];

        $this->db->insert($this->logsTable, [
            'api_key_id'   => $data['api_key_id'] ?? null,
            'lead_id'      => $data['lead_id'] ?? null,
            'status'       => $data['status'] ?? 'error',
            'message'      => $data['message'] ?? '',
            'payload'      => public_leads_api_compact_payload($payload),
            'ip_address'   => $data['ip'] ?? '',
            'response_code'=> $data['code'] ?? 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function recent_logs(int $limit = 20): array
    {
        return $this->db->order_by('id', 'desc')->limit($limit)->get($this->logsTable)->result_array();
    }

    /**
     * Build lead payload and dynamic custom fields.
     */
    public function prepare_lead(array $payload): array
    {
        $leadFields   = public_leads_api_lead_fields();
        $leadData     = [];
        $customFields = [];

        $defaultStatus = $this->get_default_status_id();
        $defaultSource = $this->get_default_source_id();

        foreach ($leadFields as $field) {
            $value           = $payload[$field] ?? '';
            $leadData[$field]= public_leads_api_default($field, $value);
        }

        // Override defaults for status/source if provided
        $leadData['status'] = $this->resolve_status($payload['status'] ?? $defaultStatus, $defaultStatus);
        $leadData['source'] = $this->resolve_source($payload['source'] ?? $defaultSource, $defaultSource);

        // Normalize tags
        if (isset($payload['tags'])) {
            $tags = $payload['tags'];
            if (is_string($tags)) {
                $tags = array_filter(array_map('trim', explode(',', $tags)));
            }
            $leadData['tags'] = $tags;
        }

        foreach ($payload as $key => $value) {
            if (in_array($key, $leadFields, true) || $key === 'tags' || public_leads_api_should_ignore($key)) {
                continue;
            }

            $fieldId = $this->ensure_custom_field($key);
            if ($fieldId) {
                $customFields[$fieldId] = public_leads_api_default($key, $value);
            }
        }

        $leadData['custom_fields'] = ['leads' => $customFields];

        return $leadData;
    }

    private function get_default_status_id(): int
    {
        // Prefer explicit "New" status if it exists
        $this->db->where('LOWER(name)', 'new lead');
        $row = $this->db->get(db_prefix() . 'leads_status')->row();
        if ($row) {
            return (int) $row->id;
        }

        // Fallback to first by order
        $this->db->order_by('statusorder', 'asc');
        $status = $this->db->get(db_prefix() . 'leads_status')->row();

        return $status ? (int) $status->id : 0;
    }

    private function get_default_source_id(): int
    {
        $this->db->order_by('id', 'asc');
        $source = $this->db->get(db_prefix() . 'leads_sources')->row();

        return $source ? (int) $source->id : 0;
    }

    private function resolve_status($input, int $fallback): int
    {
        if (is_numeric($input)) {
            return (int) $input;
        }

        if (is_string($input) && $input !== '') {
            $this->db->where('LOWER(name)', strtolower($input));
            $row = $this->db->get(db_prefix() . 'leads_status')->row();
            if ($row) {
                return (int) $row->id;
            }
        }

        return $fallback;
    }

    private function resolve_source($input, int $fallback): int
    {
        if (is_numeric($input)) {
            return (int) $input;
        }

        if (is_string($input) && $input !== '') {
            $this->db->where('LOWER(name)', strtolower($input));
            $row = $this->db->get(db_prefix() . 'leads_sources')->row();
            if ($row) {
                return (int) $row->id;
            }
        }

        return $fallback;
    }

    /**
     * Ensure a leads custom field exists; create on the fly when missing.
     */
    public function ensure_custom_field(string $key): ?int
    {
        $slug = slug_it('leads_' . $key, ['separator' => '_']);

        $this->db->where('fieldto', 'leads');
        $this->db->group_start();
        $this->db->where('slug', $slug);
        $this->db->or_where('LOWER(name)', strtolower($key));
        $this->db->group_end();
        $existing = $this->db->get(db_prefix() . 'customfields')->row();

        if ($existing) {
            return (int) $existing->id;
        }

        $this->load->model('custom_fields_model');
        $label = public_leads_api_label_from_key($key);

        $data = [
            'fieldto'                 => 'leads',
            'name'                    => $label,
            'type'                    => 'input',
            'options'                 => '',
            'bs_column'               => 12,
            'display_inline'          => 0,
            'only_admin'              => 0,
            'show_on_table'           => 0,
            'required'                => 0,
            'show_on_pdf'             => 0,
            'show_on_client_portal'   => 0,
            'disalow_client_to_edit'  => 0,
            'field_order'             => 0,
            'default_value'           => '',
        ];

        $newId = $this->custom_fields_model->add($data);

        return $newId ? (int) $newId : null;
    }
}
