<?php

class Payamito_Jet_User extends Payamito_Jet_Base
{
    public function __construct(array $settings)
    {
        parent::__construct($settings);
    }

    public function user_send(array $value)
    {
        $phone = $value[$this->settings['to']];
        if (payamito_verify_moblie_number($phone)) {
            $send = parent::send($phone, $value);
        }
        return $send;
    }
    public function apb_send()
    {
        $data = $this->apb_get_data();
        $phone = $data[$this->settings['to']];
        if (!is_null($this->phone)) {
            $phone = $this->phone;
        }
        if (payamito_verify_moblie_number($phone)) {
            $send = parent::send($phone, $data);
        }
        if ($send['result'] === true) {
            if ($this->is_multi_apb === true) {
                $this->apb_insert_phone($this->appointments, $phone);
            } else {
                $this->apb_insert_phone([$this->appointment], $phone);
            }
        }
        return $send;
    }
}
