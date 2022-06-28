<?php

class Payamito_Jet_Appointmet
{
    public $formID;
    private $settings;
    public $action;
    public $clients = ['admin', 'user'];
    public $apb;
    public $phone = null;

    public function __construct()
    {
        add_action("jet-apb/form/notification/success", [$this, 'new_appointment'], 10, 2);
        add_action('activated_plugin', [$this, 'update_table'], 10, 1);
        add_filter("rest_request_after_callbacks", [$this, 'update_appointment'], 10, 3);
        add_filter("payamito_jet_apb_to_jalali_date", [$this, 'jalali_converter'], 10, 3);
        add_filter("jet-apb/admin/helpers/page-config/config", [$this, 'page_config'], 10, 1);
    }
    public function page_config($page_config)
    {
        if (isset($page_config['config']['columnsVisibility'])) {
            array_push($page_config['config']['columnsVisibility'], "pjet_phone");
            array_push($page_config['config']['columnsVisibility'], "pjet_formid");
            $page_config['config']['labels']['pjet_phone'] = __("Payamito Phone", PAYAMITO_JET_Text_Domain);
            $page_config['config']['labels']['pjet_formid'] = __("Form ID", PAYAMITO_JET_Text_Domain);
            return  $page_config;
        }
        return $page_config;
    }

    public function update_appointment($response, $handler, $request)
    {
        if ($response  instanceof WP_REST_Response) {
            $data = $response->get_data();
            if (!isset($data['success']) || $data['success'] === false) {
                return $response;
            }
            $is_apbRequest = false;
            $permission_callback = $handler['permission_callback'];
            foreach ($permission_callback as $callback) {
                if ($callback instanceof JET_APB\Rest_API\Endpoint_Update_Appointment) {
                    $is_apbRequest = true;
                    break;
                }
            }
            if ($is_apbRequest === false) {
                return $response;
            }
            $data = (array)json_decode($request->get_body());
            $data = (array)$data['item'];
            if (isset($data['pjet_formid']) && !is_null($data['pjet_formid'])) {
                $this->init($data['pjet_formid']);
                $this->clients_init([$data]);
            }
        }

        return $response;
    }
    public function new_appointment($appointments, $action)
    {
        $formID = $action->getFormId();
        if (!$this->init($formID, $action)) {
            return;
        }

        if (!is_array($appointments)) {
            $appointments = (array)$appointments;
        }
        $this->add_more_info($formID, $appointments);
        $this->clients_init($appointments);
    }
    public function add_more_info($formID, $appointments)
    {
        global $wpdb;
        $table = $wpdb->prefix . "jet_appointments";
        foreach ($appointments as $appointment) {
            $wpdb->update($table, ['pjet_formid' => $formID], ["ID" => $appointment['ID']]);
        }
    }
    public function clients_init($appointments)
    {
        $appointment_filtered = [];
        foreach ($this->clients as $client) {
            if ($this->settings[$client]['active'] === false) {
                continue;
            }
            foreach ($appointments as $index =>  $appointment) {
                $apb = self::get_appointment($appointment['ID']);
                $apb_setting = $this->settings[$client]['apb'][$apb->status];
                $appointment_filtered[$index]['date'] = apply_filters("payamito_jet_apb_{$client}_date_format", $appointment['date'], $apb_setting, $this);
                $appointment_filtered[$index]['slot'] = apply_filters("payamito_jet_apb_{$client}_time_slot", $appointment['slot'], $apb_setting, $this);
                $appointment_filtered[$index]['slot_end'] = apply_filters("payamito_jet_apb_{$client}_time_slot_end", $appointment['slot_end'], $apb_setting, $this);
                $appointment_filtered[$index]['provider'] = $appointment['provider'];
                $appointment_filtered[$index]['service'] = $appointment['service'];
                $appointment_filtered[$index]['ID'] = $appointment['ID'];

                if (isset($appointment['pjet_phone']) && !is_null($appointment['pjet_phone'])) {
                    $appointment_filtered[$index]['phone'] = $appointment['pjet_phone'];
                    $this->phone = $appointment['pjet_phone'];
                }
            }
            if ($apb_setting['active'] === true) {
                do_action("payamito_jet_apb_{$client}", $appointment_filtered, $apb_setting, $this);
            }
        }
    }

    public function init($formID, $action = null)
    {
        if (!payamito_jet_is_installed_apb()) {
            return false;
        }
        $this->formID = $formID;
        $this->settings = payamito_jet_get_settings($formID);
        $this->action = $action;
        if ($this->settings['user']['active'] === false && $this->settings['admin']['active'] === false) {
            return;
        }

        foreach ($this->clients as $client) {
            add_filter("payamito_jet_apb_{$client}_date_format", [$this, 'date_format'], 10, 2);
            add_filter("payamito_jet_apb_{$client}_time_slot", [$this, 'slot_format'], 10, 2);
            add_filter("payamito_jet_apb_{$client}_time_slot_end", [$this, 'slot_end_format'], 10, 2);
            add_action("payamito_jet_apb_{$client}", [$this, $client], 10, 2);
        }
        if ($this->settings['general']['payamito_wc_compatibility'] === true) {
            new Payamito_Jet_Wc_Integration;
        }

        return true;
    }

    public function admin($appointments, $settings)
    {
        $admin = new Payamito_Jet_Admin($settings);
        $admin->action = 'appointment';
        $this->send($admin, $appointments, $settings);
    }
    public function user($appointments, $settings)
    {
        $user = new Payamito_Jet_User($settings);
        $user->action = 'appointment';
        if (!is_null($this->phone)) {
            $user->phone = $this->phone;
        }
        $this->send($user, $appointments, $settings);
    }
    public function send($obj, $appointments, $settings)
    {
        $multi_apb = "";
        if ($settings['multi_appointments'] === true) {
            if ($settings['custom_slm'] === true) {
                $multi_apb = self::custom_multi_appointments($appointments, $settings['slm']);
            } else {
                $multi_apb = self::default_multi_appointments($settings['multi_appointments_default'], $appointments);
            }
            $obj->multi_apb = $multi_apb;
            $obj->is_multi_apb = false;
            $obj->appointments = $appointments;
            $obj->apb_send();
        } else {
            $obj->is_multi_apb = false;
            foreach ($appointments as $appointment) {
                $obj->multi_apb = self::default_multi_appointments("a", [$appointment]);
                $obj->appointment = $appointment;
                $obj->apb_send();
            }
        }
    }
    public function is_status_active($status)
    {
        if ($this->settings[$status]['active'] === true) {
            return true;
        }
        return false;
    }

    public function jalali_converter($date, $s, $format)
    {
        $format = explode($s, $format);
        $y = array_search("Y", $format);
        $m = array_search("m", $format);
        $d = array_search("d", $format);

        $date = explode($s, $date);
        $date = payamito_jalali_converter($date[$y], $date[$m], $date[$d]);
        $f[$y] = $date[0];
        $f[$m] = $date[1];
        $f[$d] = $date[2];
        ksort($f);
        $date = implode($s, $f);
        return $date;
    }
    public function date_format($value, $setting)
    {
        $value = date_i18n($setting['date_format'], $value);
        $date = apply_filters("payamito_jet_apb_to_jalali_date", $value, $setting['date_s'], $setting['date_format']);
        return $date;
    }
    public function slot_format($value, $setting)
    {
        return $value;
    }
    public function slot_end_format($value, $setting)
    {
        return $value;
    }

    public static function get_appointment($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "jet_appointments";
        $sql = $wpdb->prepare("SELECT * FROM `{$table_name}` WHERE ID=%s", $id);
        $result = $wpdb->get_row($sql);
        return $result;
    }

    public static function  default_multi_appointments($id, $appointments)
    {
        switch ($id) {
            case "a":
                return self::default_a($appointments);
                break;
            case "b":
                return self::default_b($appointments);
                break;
            case "c":
                return self::default_c($appointments);
                break;
            case "d":
                return self::default_c($appointments);
                break;
            default:
                return "";
        }
    }

    public static function default_a($appointments)
    {
        $multi_apb = "";
        $max = max(array_keys($appointments));
        foreach ($appointments as $index => $appointment) {

            if ($max !== $index) {
                $multi_apb .= sprintf("%s %s-%s \n", $appointment['date'], $appointment['slot'], $appointment['slot_end']);
            } else {
                $multi_apb .= sprintf("%s %s-%s", $appointment['date'], $appointment['slot'], $appointment['slot_end']);
            }
        }
        return $multi_apb;
    }
    public static function default_b($appointments)
    {
        $multi_apb = "";
        $max = max(array_keys($appointments));
        foreach ($appointments as $index => $appointment) {
            if ($max !== $index) {
                $multi_apb .= sprintf("%s %s %s %s %s \n", $appointment['date'], __("Start", PAYAMITO_JET_Text_Domain), $appointment['slot'], __("End", PAYAMITO_JET_Text_Domain), $appointment['slot_end']);
            } else {
                $multi_apb .= sprintf("%s %s %s %s %s", $appointment['date'], __("Start", PAYAMITO_JET_Text_Domain), $appointment['slot'], __("End", PAYAMITO_JET_Text_Domain), $appointment['slot_end']);
            }
        }
        return $multi_apb;
    }
    public static function default_c($appointments)
    {
        $multi_apb = "";
        $max = max(array_keys($appointments));
        foreach ($appointments as $index => $appointment) {
            if ($max !== $index) {
                $multi_apb .= sprintf("%s %s %s \n", $appointment['date'], $appointment['slot'], $appointment['slot_end']);
            } else {
                $multi_apb .= sprintf("%s %s %s", $appointment['date'], $appointment['slot'], $appointment['slot_end']);
            }
        }
        return $multi_apb;
    }
    public static function default_d($appointments)
    {
        $multi_apb = "";
        $max = max(array_keys($appointments));
        foreach ($appointments as $index => $appointment) {
            if ($max !== $index) {
                $multi_apb .= sprintf("%s %s %s %s %s \n", __("Date", PAYAMITO_JET_Text_Domain), $appointment['date'], $appointment['slot'], __("To", PAYAMITO_JET_Text_Domain), $appointment['slot_end']);
            } else {
                $multi_apb .= sprintf("%s %s %s %s %s", __("Date", PAYAMITO_JET_Text_Domain), $appointment['date'], $appointment['slot'], __("To", PAYAMITO_JET_Text_Domain), $appointment['slot_end']);
            }
        }
        return $multi_apb;
    }
    public static function custom_multi_appointments($appointments, $slm)
    {
        $multi_apb = "";
        $max = max(array_keys($appointments));
        $count = 1;
        foreach ($appointments as $index => $appointment) {

            if ($max !== $index) {
                $multi_apb .= sprintf(str_replace(["dt", "st", "et"], [$appointment['date'], $appointment['slot'], $appointment['slot_end']], $slm,)) . " \n";
            } else {
                $multi_apb .= sprintf(str_replace(["dt", "st", "et"], [$appointment['date'], $appointment['slot'], $appointment['slot_end']], $slm,));
            }
        }
        return $multi_apb;
    }
    public function update_table($plugin)
    {
        if ($plugin === 'jet-appointments-booking/jet-appointments-booking.php') {
            payamito_jet_insert_table_column("pjet_formid");
            payamito_jet_insert_table_column("pjet_phone");
        }
    }
}
