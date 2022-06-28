<?php

class Payamito_Jet_Admin extends Payamito_Jet_Base
{

    public function __construct(array $settings)
    {
        parent::__construct($settings);
    }
    public function admin_send(array $value)
    {
        $phones = $this->settings['to'];
        if (is_array($phones)) {
            foreach ($phones as $phone) {
                if (payamito_verify_moblie_number($phone['phone_number'])) {
                    $send = parent::send($phone['phone_number'], $value);
                }
            }
        }
        return $send;
    }

    public function apb_send()
    {
        $data = $this->apb_get_data();
        $phones = $this->settings['to'];
        if (is_array($phones)) {
            foreach ($phones as $phone) {
                if (payamito_verify_moblie_number($phone['phone_number'])) {
                    $send = parent::send($phone['phone_number'], $data);
                    if ($send['result'] === true) {
                        if ($this->is_multi_apb === true) {
                            $this->apb_insert_phone($this->appointments, $phone);
                        } else {
                            $this->apb_insert_phone([$this->appointment], $phone);
                        }
                    }
                }
            }
        }
        return $send;
    }
}
