<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'pla_api_logs`');
$CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'pla_api_keys`');

delete_option('public_leads_api_enabled');
delete_option('public_leads_api_rate_limit_per_minute');
