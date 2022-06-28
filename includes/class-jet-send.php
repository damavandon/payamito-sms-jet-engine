<?php

if (!class_exists("Payamito_Jet_Send")) :
    class Payamito_Jet_Send
    {
        public static function pattern_send(string $phone_number, array $pattern, int $pattern_id)
        {
            $send = (int)payamito_send_pattern($phone_number, $pattern, $pattern_id, Payamito_Jet_Engine_Loader::$slug);
            if ($send > 10000) {
                $result['result'] = true;
                $result['message'] = __("successful", PAYAMITO_JET_Text_Domain);
            } else {
                $result['result'] = false;
                $result['message'] = payamito_code_to_message($send);
            }
            return $result;
        }

        public static function text_send(string $phone_number, string $message)
        {

            $send = (int) payamito_send($phone_number, $message, Payamito_Jet_Engine_Loader::$slug);
            if ($send === 1) {
                $result['result'] = true;
                $result['message'] = __("successful", PAYAMITO_JET_Text_Domain);
            } else {
                $result['result'] = false;
                $result['message'] = payamito_code_to_message($send);
            }
            return $result;
        }
    }
endif;
