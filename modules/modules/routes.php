<?php
defined('BASEPATH') or exit('No direct script access allowed');

$route['nexreports/project_reports'] = 'nexreports/project_reports';
$route['nexreports/projects'] = 'nexreports/projects';

// DataTable AJAX endpoint
$route['nexreports/table'] = 'nexreports/table';

// Export endpoint
$route['nexreports/export'] = 'nexreports/export';

// Summary data (for charts/cards)
$route['nexreports/summary'] = 'nexreports/summary';
