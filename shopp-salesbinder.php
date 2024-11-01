<?php

/*
Plugin Name: Shopp + SalesBinder
Plugin URI: http://wordpress.org/extend/plugins/shopp-salesbinder/
Description:
Author: SalesBinder Development Team
Author URI: http://www.salesbinder.com/tour/api-integrations/
Version: 1.1.2
*/

defined('WPINC') || header('HTTP/1.1 403') & exit;

require_once dirname(__FILE__) . '/api/salesbinder.php';
require_once dirname(__FILE__) . '/core/library/SalesBinder.php';
require_once dirname(__FILE__) . '/core/flow/Setup.php';

add_action('plugins_loaded', array('Shopp_SalesBinder', 'plugin'), 99);
