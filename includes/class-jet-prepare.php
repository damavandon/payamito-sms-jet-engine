<?php

class Payamito_Jet_Prepare
{
    public $options;

    public static $OTP = null;

    public static $count;
    public static function prepare_options($options, $setting)
    {
        $p_options = [];
        $post_id = $setting->post_id;
        $general_slug = Payamito_Jet_Engine_Metabox::get_general_slug() . '_';
        $otp_slug = Payamito_Jet_Engine_Metabox::get_otp_slug() . '_';
        $user_slug = Payamito_Jet_Engine_Metabox::get_user_slug() . '_';
        $admin_slug = Payamito_Jet_Engine_Metabox::get_admin_slug() . '_';

        $p_options["general"]['payamito_wc_compatibility'] = $options[$general_slug . 'payamito_wc_compatibility'] === '1' ? true : false;
        $p_options["general"]['jalali_converter'] = $options[$general_slug . 'jalali_converter'] === '1' ? true : false;


        $p_options["OTP"]['active'] = $options[$otp_slug . 'active'] === '1' ? true : false;
        $p_options["OTP"]['to'] = $options[$otp_slug . 'field'];
        $p_options["OTP"]['count'] = $options[$otp_slug . 'count'];
        $p_options["OTP"]['resend'] = $options[$otp_slug . 'resend'];
        $p_options["OTP"]['pattern_active'] = $options[$otp_slug . 'pattern_active'] === '1' ? true : false;
        $p_options["OTP"]['pattern_id'] = $options[$otp_slug . 'pattern_id'];
        $p_options["OTP"]['pattern'] = is_array($options[$otp_slug . 'pattern']) ? $options[$otp_slug . 'pattern'] : [];
        $p_options["OTP"]['text'] = $options[$otp_slug . 'text'];
        $p_options["OTP"]['lable'] = $options[$otp_slug . 'lable'];
        $p_options["OTP"]['placeholder'] = $options[$otp_slug . 'placeholder'];
        $p_options["OTP"]['resend_button'] = $options[$otp_slug . 'resend_button'];
        $p_options["OTP"]['post_id'] = $post_id;

        $p_options["user"]['active'] = $options[$user_slug . 'active'] === '1' ? true : false;
        $p_options["user"]['to'] = $options[$user_slug . 'field'];
        $p_options["user"]['pattern_active'] = $options[$user_slug . 'pattern_active'] === '1' ? true : false;
        $p_options["user"]['pattern_id'] = $options[$user_slug . 'pattern_id'];
        $p_options["user"]['pattern'] = is_array($options[$user_slug . 'pattern']) ? $options[$user_slug . 'pattern'] : [];
        $p_options["user"]['text'] = $options[$user_slug . 'text'];


        $p_options["admin"]['active'] = $options[$admin_slug . 'active'] === '1' ? true : false;
        $p_options["admin"]['to'] = $options[$admin_slug . 'phone'];
        $p_options["admin"]['pattern_active'] = $options[$admin_slug . 'pattern_active'] === '1' ? true : false;
        $p_options["admin"]['pattern_id'] = $options[$admin_slug . 'pattern_id'];
        $p_options["admin"]['pattern'] = is_array($options[$admin_slug . 'pattern']) ? $options[$admin_slug . 'pattern'] : [];
        $p_options["admin"]['text'] = $options[$admin_slug . 'text'];

        //wooccomerce
        if ($setting->apb_field_added === true) {
            $apb = [];
            foreach (['user', 'admin'] as $slug) {

                foreach ($setting->apb_status as $apb) {
                    $option = $options[$slug . '_' . $apb['status']];
                    $date_s = explode("|", $option['date_format'])[0];
                    if($slug==='admin'){
                        $apb_status[$apb['status']]['to'] = $options[$slug . '_phone'];
                    }
                    if($slug==='user'){
                        $apb_status[$apb['status']]['to'] = $options[$slug . '_field'];
                    }
                    $apb_status[$apb['status']]['active'] = $option['active'] === '1' ? true : false;
                    $apb_status[$apb['status']]['pattern_active'] = $option['pattern_active'] === '1' ? true : false;
                    $apb_status[$apb['status']]['pattern_id'] = $option['pattern_id'];
                    if (isset($option['pattern']) && is_array($option['pattern'])) {
                        $apb_status[$apb['status']]['pattern'] = $option['pattern'];
                    }
                    $apb_status[$apb['status']]['pattern'] = $option['pattern'];
                    $apb_status[$apb['status']]['text'] = $option['text'];
                    $apb_status[$apb['status']]['date_format'] = str_replace(["-|", "/|"], '', $option['date_format']);
                    $apb_status[$apb['status']]['date_s'] = $date_s;
                }
                $p_options[$slug]['apb'] = $apb_status;
                $apb_status = [];
            }
        }
        update_post_meta($post_id, "payamito", serialize($p_options));
    }
}
