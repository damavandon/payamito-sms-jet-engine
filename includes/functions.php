<?php

if (!function_exists('payamito_jet_load_core')) {

    function payamito_jet_load_core()
    {
        $core = get_option("payamito_core_version");
        if ($core === false) {
            return PAYAMITO_JET_CORE_DIR;
        }
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $core = unserialize($core);
        if (
            file_exists($core['core_path'])
            &&
            is_plugin_active($core['absolute_path'])
        ) {
            return $core['core_path'];
        } else {
            return PAYAMITO_JET_CORE_DIR;
        }
        return PAYAMITO_JET_CORE_DIR;
    }
}
if (!function_exists("_payamito_jet_no_intalled_jet_engine")) {
    function _payamito_jet_no_intalled_jet_engine()
    {
        $jet_engine = "https://abzarwp.com/downloads/jet-engine/";
        $message =  __('Payamito jet engine  is not working because you need to activate the Jet Engine', PAYAMITO_JET_Text_Domain);
?>
        <div class="notice notice-error is-dismissible" style="padding: 2%;border: 2px solid #e39e06;">
            <p style="text-align: center;font-size: 19px;font-weight: 700;"><?php esc_html_e($message); ?></p>
            <p><a target="_blank" href="<?php echo esc_url($jet_engine) ?>" class="button-primary"> <?php esc_html_e('Install Jet Engine Now', PAYAMITO_JET_Text_Domain); ?></a></p>
        </div>
<?php
    }
}

if (!function_exists("payamito_jet_is_installed_payamito_wc")) {
    function payamito_jet_is_installed_payamito_wc()
    {
        return defined("PAYAMITO_WC_PLUGIN_FILE");
    }
}

if (!function_exists("payamito_jet_is_installed_jet_appointments")) {
    function payamito_jet_is_installed_jet_appointments()
    {
        return defined("JET_APB__FILE__");
    }
}

function payamito_jet_remove_class_filter($tag, $class_name = '', $method_name = '', $priority = 10)
{
    global $wp_filter;
    // Check that filter actually exists first
    if (!isset($wp_filter[$tag])) {
        return FALSE;
    }

    if (is_object($wp_filter[$tag]) && isset($wp_filter[$tag]->callbacks)) {
        // Create $fob object from filter tag, to use below
        $fob       = $wp_filter[$tag];
        $callbacks = &$wp_filter[$tag]->callbacks;
    } else {
        $callbacks = &$wp_filter[$tag];
    }

    if (!isset($callbacks[$priority]) || empty($callbacks[$priority])) {
        return FALSE;
    }

    foreach ((array) $callbacks[$priority] as $filter_id => $filter) {

        if (!isset($filter['function']) || !is_array($filter['function'])) {
            continue;
        }

        if (!is_object($filter['function'][0])) {
            continue;
        }

        if ($filter['function'][1] !== $method_name) {
            continue;
        }

        if (get_class($filter['function'][0]) === $class_name) {

            if (isset($fob)) {

                $fob->remove_filter($tag, $filter['function'], $priority);
            } else {

                unset($callbacks[$priority][$filter_id]);

                if (empty($callbacks[$priority])) {
                    unset($callbacks[$priority]);
                }

                if (empty($callbacks)) {
                    $callbacks = array();
                }
                unset($GLOBALS['merged_filters'][$tag]);
            }
            return TRUE;
        }
    }
    return FALSE;
}
if (!function_exists("payamito_jet_get_settings")) {
    function payamito_jet_get_settings($form_id)
    {
        $settings = unserialize(get_post_meta($form_id, "payamito", true));
        return $settings;
    }
}
if (!function_exists("payamito_jet_is_installed_apb")) {
    function payamito_jet_is_installed_apb()
    {
        return defined("JET_APB_VERSION");
    }
}

if (!function_exists("payamito_jet_insert_table_column")) {
    function payamito_jet_insert_table_column($column)
    {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            return;
        }

        $table = $wpdb->prefix . "jet_appointments";
        
        $sql = "ALTER TABLE $table ADD  $column bigint(20)";
        $result = $wpdb->query($sql);
    }
}
