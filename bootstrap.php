<?php

/**
 * @package   Payamito
 * @link      https://payamito.com/
 *
 * Plugin Name:       Payamito Jet Engine
 * Plugin URI:        https://payamito.com/lib
 * Description:       Payamito Jet Engine plugin
 * Version:           1.2.0
 * Author:            Payamito
 * Author URI:        https://payamito.com/
 * Text Domain:       payamito-jet-engine     
 * Domain Path:       /languages
 * Requires PHP: 7.4
 */

// don't call the file directly.
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('PAYAMITO_JET_ENGINE_PLUGIN_FILE')) {

    define('PAYAMITO_JET_ENGINE_PLUGIN_FILE', __FILE__);
}


//all things start to be here
include_once __DIR__ . '/includes/loader.php';

function payamito_jet()
{
   return Payamito_Jet_Engine_Loader::get_instance();
}
payamito_jet();