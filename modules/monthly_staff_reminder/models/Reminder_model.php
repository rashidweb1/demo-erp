<?php defined('BASEPATH') or exit('No direct script access allowed');

class Reminder_model extends CI_Model
{
    public function handle_monthly_mail()
    {
        $CI = &get_instance();
        $CI->load->helper('monthly_staff_reminder_helper');
        log_activity('MSR CRON RUNNING');
        // Read options
        $test_mode      = (int) get_option('msr_test_mode');
        $test_staff_ids = trim(get_option('msr_test_staff_ids'));
        $last_run       = trim(get_option('msr_last_run'));

        $now = new DateTime('now');
        $current_month = $now->format('Y-m');
        $current_day   = (int) $now->format('j');
        $current_time  = $now->format('H:i');

        // -----------------------------------------
        // TEST MODE — Sends on every cron execution
        // -----------------------------------------
        if ($test_mode === 1) {

            $ids = [];
            if (!empty($test_staff_ids)) {
                $ids = array_filter(array_map('trim', explode(',', $test_staff_ids)));
            }

            if (empty($ids)) {
                log_activity('MSR: Test mode enabled but no staff IDs in msr_test_staff_ids.');
                return false;
            }

            $staff_list = $this->get_staff_by_ids($ids);

            if (empty($staff_list)) {
                log_activity('MSR: No staff found for test IDs: ' . $test_staff_ids);
                return false;
            }

            return msr_send_emails($staff_list);
        }

        // -----------------------------------------
        // LIVE MODE — 1st day, 9:00–9:20, once/month
        // -----------------------------------------
        if ($current_day !== 1) {
            return false;
        }

        if (!($current_time >= '09:00' && $current_time < '09:20')) {
            return false;
        }

        if ($last_run === $current_month) {
            return false; // already executed this month
        }

        $staff_list = $this->get_all_active_staff();

        if (empty($staff_list)) {
            log_activity('MSR: No active staff found.');
            return false;
        }

        $result = msr_send_emails($staff_list);

        update_option('msr_last_run', $current_month);

        return $result;
    }

    // --------------------------
    // Helpers
    // --------------------------
    private function get_staff_by_ids(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $this->db->where_in('staffid', $ids);
        $this->db->where('active', 1);
        return $this->db->get(db_prefix() . 'staff')->result();
    }

    private function get_all_active_staff()
    {
        $this->db->where('active', 1);
        return $this->db->get(db_prefix() . 'staff')->result();
    }
}
