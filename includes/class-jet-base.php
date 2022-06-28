<?php


class Payamito_Jet_Base
{
    public $settings;
    public $is_multi_apb = false;
    public $multi_apb = null;
    public $appointment = null;
    public $appointments = null;
    public $phone = null;
    public $action;
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }
    public function send(string $phone, array $value)
    {
        if ($this->is_pattern_active()) {

            
            if ($this->action === 'appointment'){
                $text = $this->prepare_apb_pattern($this->settings['pattern']);
            } else {
                $text = $this->prepare_pattern($this->settings['pattern'], $value);
            }
            $send = Payamito_Jet_Send::pattern_send($phone, $text, $this->settings['pattern_id']);
        } else {
            if ($this->action === 'appointment') {
                $text = $this->prepare_apb_text($this->settings['text']);
            } else {
                $text = $this->prepare_text($this->settings['text'], $value);
            }

            $send = Payamito_Jet_Send::text_send($phone, $text);
        }

        return $send;
    }
    public  function prepare_apb_pattern($pattern)
    {
        $ready_pattern = [];

        foreach ($pattern as $item) {
            switch ($item[0]) {
                case "service":
                case "provider":
                case "date":
                case "slot":
                case "ID":
                case "price":
                case "slot_end":
                    $ready_pattern[$item[1]] = $this->appointment[$item[0]];
                    break;
                default:
                    if (is_user_logged_in()) {
                        $ready_pattern[$item[1]] = get_user_meta(get_current_user_id(), $item[0], true);
                    } else {
                        $ready_pattern[$item[1]] = "";
                    }
            }
        }
        return $ready_pattern;
    }
    public function prepare_apb_text($text)
    {
        if (empty($text)) {
            return "";
        }
        $replced = self::explode($text);

        foreach ($replced as $id => $item) {

            if (!empty(trim($item))) {
                switch (trim($item)) {
                    case "service":
                        $replced[$id] = $this->appointment['service'];
                        break;
                    case "provider":
                        $replced[$id] = $this->appointment['provider'];
                        break;
                    case "date":
                        $replced[$id] = $this->appointment['date'];
                        break;
                    case "slot":
                        $replced[$id] = $this->appointment['slot'];
                        break;
                    case "ID":
                        $replced[$id] = $this->appointment['ID'];
                        break;
                    case "slot_end":
                        $replced[$id] = $this->appointment['slot_end'];
                        break;
                    case "price":
                        $replced[$id] = $this->appointment['price'];
                        break;
                    default:
                        if (is_user_logged_in()) {
                            $meta = get_user_meta(get_current_user_id(), trim($item), true);
                            if ($meta !== false && !empty($meta)) {
                                $replced[$id] = $meta;
                            }
                        }
                }
            }
        }
        $replced = implode(" ", $replced);
        $replced = self::str_replace($replced);
        return $replced;
    }
    public function is_active()
    {
        if ($this->settings['active'] === true) {
            return true;
        }
        return false;
    }
    public function is_pattern_active()
    {
        if ($this->settings['pattern_active'] === true) {
            return true;
        }
        return false;
    }

    public  function prepare_pattern(array $pattern, array $fields)
    {
        $prepared_pattern = [];
        $ready_pattern = [];

        foreach ($pattern as $item) {
            $prepared_pattern[$item['1']] = explode('|', $item[0]);
        }
        foreach ($prepared_pattern as $key =>  $tag) {
            foreach ($fields as $id => $value) {
                if ($id === $tag[1]) {
                    switch ($tag[0]) {
                        case "text":
                        case "number":
                        case "calculated":
                            $ready_pattern[$key] = trim($value);
                            break;
                    }
                }
            }
        }
        return $ready_pattern;
    }

    public  function prepare_text(string $message, array $sent_data)
    {
        if (empty($message)) {
            return "";
        }
        $replced = self::explode($message);
        foreach ($replced as  $item) {
            foreach ($sent_data as $field_id => $data) {
                if ($item === $field_id) {
                    array_push($search, $field_id);
                }
            }
        }
        $text = str_replace(array_values($search), array_values($sent_data), $message);
        $text = self::str_replace($text);
        return $text;
    }
    public static function explode(string $message)
    {

        $message = trim(str_replace(PHP_EOL, ' /n ', $message));
        $search = explode(" ", $message);
        return $search;
    }
    public static function str_replace(string $text)
    {
        $text = trim(str_replace(' /n ', PHP_EOL, $text));
        return $text;
    }
    public function apb_get_data()
    {
        $data = [];
        if (!wp_doing_ajax()) {
            $data = array_map("sanitize_text_field", $_POST);
        } else {
            foreach ($_POST['values'] as $value) {
                $data[$value['name']] = sanitize_text_field($value['value']);
            }
        }
        return $data;
    }
    public function apb_insert_phone($appointments, $phone)
    {
        global $wpdb;
        $table = $wpdb->prefix . "jet_appointments";
        foreach ($appointments as $appointment) {
            $wpdb->update($table, ['pjet_phone' => $phone], ["ID" => $appointment['ID']]);
        }
    }
}
