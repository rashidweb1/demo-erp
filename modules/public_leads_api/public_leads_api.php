<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Leads API
Description: Exposes a secure public endpoint that accepts form submissions from external sites and stores them as Perfex leads (including dynamic custom fields).
Version: 1.0.0
Requires at least: 2.3.*
Author: Codex
*/

define('PUBLIC_LEADS_API_MODULE', 'public_leads_api');

hooks()->add_action('app_init', 'public_leads_api_init');
hooks()->add_action('admin_init', 'public_leads_api_register_menu');

register_activation_hook(PUBLIC_LEADS_API_MODULE, 'public_leads_api_activate');
register_uninstall_hook(PUBLIC_LEADS_API_MODULE, 'public_leads_api_uninstall');

/**
 * Bootstrap helper(s) so shared functions are available everywhere.
 */
function public_leads_api_init()
{
    $CI = &get_instance();
    $CI->load->helper(PUBLIC_LEADS_API_MODULE . '/public_leads_api');
}

/**
 * Install callback.
 */
function public_leads_api_activate()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Uninstall callback.
 */
function public_leads_api_uninstall()
{
    require_once(__DIR__ . '/uninstall.php');
}

/**
 * Add a Setup menu entry for managing API keys/settings.
 */
function public_leads_api_register_menu()
{
    if (!is_admin()) {
        return;
    }

    $CI = &get_instance();

    $CI->app_menu->add_setup_menu_item('public-leads-api', [
        'name'     => 'Leads API',
        'href'     => admin_url('public_leads_api'),
        'position' => 45,
        'icon'     => '',
    ]);
}
