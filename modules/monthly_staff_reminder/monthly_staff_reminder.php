<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Monthly Staff Reminder
Description: Sends monthly reminders to staff about timesheet, leave, OT, and tickets.
Version: 1.0.0
Requires at least: 2.3.*
Author: Nexgeno Technology
*/


// Activation / Deactivation / Uninstall hooks
register_activation_hook('monthly_staff_reminder', 'monthly_staff_reminder_activate');
register_deactivation_hook('monthly_staff_reminder', 'monthly_staff_reminder_deactivate');
register_uninstall_hook('monthly_staff_reminder', 'monthly_staff_reminder_uninstall');


function monthly_staff_reminder_activate() {
require_once(__DIR__ . '/install.php');
monthly_staff_reminder_install();
}


function monthly_staff_reminder_deactivate() {
// optional cleanup on deactivate
}


function monthly_staff_reminder_uninstall() {
// optional cleanup on uninstall
}


// Register cron hook
hooks()->add_action('after_cron_run', 'monthly_staff_reminder_run_cron');


function monthly_staff_reminder_run_cron() {
$CI = &get_instance();


// load model when CI is ready
$CI->load->model('monthly_staff_reminder/Reminder_model');


// ensure helper is available (helper file name must be monthly_staff_reminder_helper.php)
$CI->load->helper('monthly_staff_reminder');


// call model handler
$CI->Reminder_model->handle_monthly_mail();
}