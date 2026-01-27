<?php
defined('BASEPATH') or exit('No direct script access allowed');


function monthly_staff_reminder_install() {
    // store last run as empty (format YYYY-MM), test mode defaults
    if (get_option('msr_last_run') === false) {
    add_option('msr_last_run', '');
}


if (get_option('msr_test_mode') === false) {
    // 1 = test mode enabled (sends every cron run to staff in msr_test_staff_ids)
    add_option('msr_test_mode', 1);
}


if (get_option('msr_test_staff_ids') === false) {
    // comma separated ids for testing; default set to 23,24,25 as requested
    add_option('msr_test_staff_ids', '23,24,25');
    }
}