<?php
class Payamito_Jet_Otp extends Payamito_Jet_Base
{
    public $OTP = null;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
    }

    public function otp_send($phone)
    {
        $form_id = $this->settings['post_id'];
        $this->count = $this->settings['count'];
        if ($this->is_pattern_active()) {

            $pattern = $this->prepare_pattern($this->settings['pattern']);
            $send = Payamito_Jet_Send::pattern_send($phone, $pattern, $this->settings['pattern_id']);
        } else {
            $message = $this->prepare_text($this->settings['text']);
            $send = Payamito_Jet_Send::text_send($phone, $message);
        }
        if ($send['result'] === true) {
            Payamito_Jet_Engine_Form::session_set($form_id, $phone, "send", true);
            Payamito_Jet_Engine_Form::session_set($form_id, $phone, "send_time", current_time("timestamp"));
            Payamito_Jet_Engine_Form::session_set($form_id, $phone, "OTP", $this->OTP);
        }
        return $send;
    }
    public  function prepare_pattern(array $pattern, array $fields = [])
    {
        $ready_pattern = [];
        foreach ($pattern as  $tag) {
            $ready_pattern[$tag[1]] = $this->otp_tag_value($tag[0]);
        }
        return $ready_pattern;
    }
    public  function  prepare_text(string $message, $fields = [])
    {
        if (empty($message)) {
            return "";
        }
        $replced = self::explode($message);
        foreach ($replced as  $item) {
            if ($item === 'OTP') {
                $message = str_replace([$item], $this->otp_tag_value($item), $message);
            }
            if ($item === 'site_title') {
                $message = str_replace([$item], $this->otp_tag_value($item), $message);
            }
        }
        return $message;
    }
    public  function otp_tag_value($tag)
    {
        switch ($tag) {
            case 'OTP':
                $this->OTP = Payamito_OTP::payamito_generate_otp($this->settings['count']);
                return $this->OTP;
                break;
            case "site_title":
                return get_bloginfo("name");
                break;
        }
    }
}
