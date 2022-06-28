<?php


if (!class_exists("Payamito_Jet_Engine_Form")) :
    class Payamito_Jet_Engine_Form
    {
        public $form;
        public $show_otp;
        public $otp;
        public $settings;
        /**
         * The single instance of the class.
         *
         * @var Payamito_Jet_Engine_Metabox
         * @since 1.0.0
         */
        private static $_instance = null;


        public static function get_instance()
        {
            if (is_null(self::$_instance)) {

                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct()
        {
            add_action('wp_ajax_payamito_jet_form_booking', array($this, 'process_ajax_form'));
            add_action('wp_ajax_nopriv_payamito_jet_form_booking', array($this, 'process_ajax_form'));
            add_filter("jet-engine/forms/booking/message-types", [$this, 'add_message'], 999, 1);

            add_action("jet-engine/forms/editor/assets", [$this, 'editor_scripts']);
            add_action("jet-engine/forms/handler/after-send", [$this, 'result'], 999, 1);
            add_action("jet-engine/forms/booking/before-start-form", [$this, 'start'], 10, 1);
            add_action('jet_plugins/frontend/register_scripts', array($this, 'register_assets'));
            add_action('jet-engine/forms/handler/before-send', [$this, 'actions_sort'], 1, 1);

            add_filter('jet-engine/forms/handler/query-args', [$this, 'add_query_args'], 999, 1);
            add_filter("jet-engine-booking/filter/payamito", [$this, 'validate'], 999, 4);
        }

        public function actions_sort($handler)
        {
            $sorted = [];
            $notifications = $handler->notifcations->notifications;
            foreach ($notifications as $index => $notifcation) {
                if ($notifcation['hook_name'] === 'payamito') {
                    $sorted[0] = $notifcation;
                    unset($notifications[$index]);
                    break;
                }
            }
            foreach ($notifications as $notifcation) {
                array_push($sorted, $notifcation);
            }
            $handler->notifcations->notifications = $sorted;
        }

        public function init($form_id)
        {
            $this->settings = payamito_jet_get_settings($form_id);
            $this->otp = new Payamito_Jet_Otp($this->settings['OTP']);
        }
        public function process_ajax_form()
        {
            $action = sanitize_text_field($_POST["actionType"]);
            if (!isset($_POST["actionType"]) || !isset($_POST["formID"])) {
                die();
            }
            $form_id = sanitize_text_field($_POST["formID"]);
            $data = array_map("sanitize_text_field", $_POST["dataSend"]);
            $this->init($form_id);
            switch ($action) {
                case "settings":
                    wp_send_json($this->settings['OTP']);
                case "resend":
                    $result = $this->resend($form_id, _payamito_el_delete_0($data['phone']));
                    wp_send_json($result);
            }
        }
        public function resend($form_id, $confirm_filed)
        {
            $this->session_start();
            $session = $this->session_get($form_id, $confirm_filed, "send_time");
            if ($session === null) {
                $resend_time = true;
            } else {
                $resend_time = $this->check_resend_time($session, $this->settings['OTP']['resend']);
            }
            if ($resend_time === true) {
                $send = $this->otp->otp_send($confirm_filed);
                if ($send['result'] === true) {

                    return ["message" => esc_html__("Send successful", PAYAMITO_JET_Text_Domain), "result" => true];
                }
                return ['message' => $send['message'], "result" => false];
            } else {
                return ["message" => sprintf(esc_html__("Please waite %s", PAYAMITO_JET_Text_Domain), $resend_time), "result" => false];
            }
        }

        public function start($form)
        {
            wp_enqueue_script('frontend-form', PAYAMITO_JET_URL . 'includes/assets/public/js/front-form.js', array("jquery"), jet_engine()->get_version(), true);
        }

        public function editor_scripts()
        {
            wp_enqueue_script(
                'payamito-jet-editor-form',
                PAYAMITO_JET_URL . 'includes/assets/admin/js/form-editor.js',
                array("jquery"),
                jet_engine()->get_version(),
                true
            );
        }
        public function result($handler)
        {
            $this->session_start();
            $handler->response_data['payamito_show_otp'] = $this->show_otp;
            $_SESSION['payamito_jet'][$handler->form]['show'] = $this->show_otp;
        }
        public function add_query_args($query_args)
        {
            if (!wp_doing_ajax()) {
                if ($this->show_otp === true) {
                    $query_args['paymito_show'] = '1';
                }
            }
            if ($query_args['status'] === 'success') {
                if (isset($query_args['payamito_show_otp'])) {
                    unset($query_args['payamito_show_otp']);
                }
                if (isset($query_args['paymito_show'])) {
                    unset($query_args['paymito_show']);
                }
            }

            return $query_args;
        }
        public function validate($result, $data, $form, $notifications)
        {
            $this->init($form);

            $this->session_start();

            if (!$this->otp->is_active($form)) {
                $this->show_otp = false;
            } else {
                $confirm_filed = payamito_to_english_number($data[$this->settings['OTP']['to']]);
                if (!payamito_verify_moblie_number($confirm_filed)) {

                    $notifications->set_specific_status('payamito_phone_invalide');
                    $this->show_otp = false;
                    $this->delete_actions($notifications);
                    return false;
                }
                $confirm_filed = _payamito_el_delete_0($confirm_filed);
                if (!self::session_is_exist($form, $confirm_filed, "send")) {
                    $send = $this->otp->otp_send($confirm_filed);
                    if ($send['result'] === true) {
                        $notifications->set_specific_status('payamito_phone_send');
                        $this->show_otp = true;
                        $this->delete_actions($notifications);
                        return false;
                    }
                    $notifications->set_specific_status($send['message']);
                    $this->show_otp = false;
                    $this->delete_actions($notifications);
                    return false;
                }
                $entry_otp = "";
                if (wp_doing_ajax()) {
                    foreach ($_REQUEST['values'] as $value) {
                        if ($value['name'] === "payamito_jet_otp_filed") {
                            $entry_otp = (int) sanitize_text_field(payamito_to_english_number($value["value"]));
                            break;
                        }
                    }
                } else {
                    $entry_otp = (int)  sanitize_text_field(payamito_to_english_number($_POST["payamito_jet_otp_filed"]));
                }

                $OTP = self::session_get($form, $confirm_filed, "OTP");

                if ($entry_otp !== $OTP) {
                    $notifications->set_specific_status("payamito_phone_otp_invalide");
                    $this->show_otp = true;
                    $this->delete_actions($notifications);
                    return false;
                }
            }
            $this->show_otp = true;
            $admin = new Payamito_Jet_Admin($this->settings['admin']);
            $user  = new Payamito_Jet_User($this->settings['user']);
            if ($admin->is_active()) {
                $admin->admin_send($data);
            }
            if ($user->is_active()) {
                $user->user_send($data);
            }
            Payamito_Jet_Wc_Integration::init($form, $this->settings, $data);
            self::session_unset($form);
            $this->show_otp = false;
            return $result;
        }

        public function delete_actions($notifications)
        {
            foreach ($notifications->notifications as $index => $action) {
                if ($action['hook_name'] !== 'payamito') {
                    payamito_jet_remove_class_filter('jet-engine/forms/booking/notification/' . $action['type'], 'Jet_Engine_Booking_Forms_Notifications', $action['type']);
                }
            }
        }

        public function add_message($messages)
        {
            $payamito_messages = [
                'payamito_phone_empty' => [
                    'label' => __("Phone number Empty", PAYAMITO_JET_Text_Domain),
                    'default' => __("Phone number is empty", PAYAMITO_JET_Text_Domain),
                ],
                'payamito_phone_invalide' => [
                    'label' => __("Phone number invalide", PAYAMITO_JET_Text_Domain),
                    'default' => __("Invalid phone number", PAYAMITO_JET_Text_Domain),
                ],
                'payamito_phone_otp_empty' => [
                    'label' => __("OTP Empty", PAYAMITO_JET_Text_Domain),
                    'default' => __("OTP is empty", PAYAMITO_JET_Text_Domain),
                ],
                'payamito_phone_otp_invalide' => [
                    'label' => __("OTP Invalide", PAYAMITO_JET_Text_Domain),
                    'default' => __("Invalide OTP", PAYAMITO_JET_Text_Domain),
                ],
                'payamito_phone_send' => [
                    'label' => __("Send OTP", PAYAMITO_JET_Text_Domain),
                    'default' => __("OTP sent to your phone number", PAYAMITO_JET_Text_Domain),
                ],
            ];

            foreach ($payamito_messages as $key => $message) {
                $messages[$key] = $message;
            }
            return $messages;
        }

        public function get_phone_field()
        {
            return 'phone_number';
        }

       
        public function session_start()
        {
            session_start();
        }
        public function session_destroy()
        {
            session_destroy();
        }
        public static function session_is_exist($form_id, $phone, $key)
        {
            $phone = _payamito_el_delete_0($phone);
            if (!in_array("payamito_jet", $_SESSION)) {
                if (array_key_exists($form_id, $_SESSION['payamito_jet'])) {
                    if (array_key_exists($key, $_SESSION['payamito_jet'][$form_id][$phone])) {
                        return true;
                    }
                    return false;
                }
                return false;
            }
            return false;
        }
        public static function  session_get($form_id, $phone, $key)
        {
            $phone = _payamito_el_delete_0($phone);
            if (self::session_is_exist($form_id, $phone, $key)) {
                return   $_SESSION['payamito_jet'][$form_id][$phone][$key];
            }
            return null;
        }
        public static function  session_unset($form_id)
        {
            unset($_SESSION['payamito_jet'][$form_id]);
        }
        public static function  session_set($form_id, $phone, $key, $value)
        {
            $phone = _payamito_el_delete_0($phone);
            $_SESSION['payamito_jet'][$form_id][$phone][$key] = $value;
        }
        public function check_resend_time($send_time, $period)
        {
            $period_send = (int)$period;
            if ($period < 10) {
                $period = 10;
            }
            $time_send = (int)$send_time;
            $R = current_time('timestamp') - $time_send;
            if ($R < $period_send) {
                return ($period_send - $R);
            }
            return true;
        }
    }
endif;
