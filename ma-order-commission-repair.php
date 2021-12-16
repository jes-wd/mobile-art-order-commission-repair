<?php

/*
Plugin Name: Mobile Art Order Commission Repair
Plugin URI: https://github.com/jes-wd/mobile-art-order-commission-repair
Description: Finds and repairs any orders that commission calculations have failed on.
Author: Jesse Sugden
Version: 1.0
Author URI: https://jeswebdevelopment.com
*/


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('MA_OCR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MA_OCR_PLUGIN_URL', plugin_dir_url(__FILE__));

include(MA_OCR_PLUGIN_PATH . 'includes/repair-all-commissions.php');
include(MA_OCR_PLUGIN_PATH . 'includes/regenerate-commission.php');