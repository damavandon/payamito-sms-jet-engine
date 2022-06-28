<?php

class Payamito_Jet_Options
{
    public $apb_status;

    public function __construct()
    {
        add_filter('payamito_add_section', [$this, 'register_settings'], 1);

        add_action('kianfr_' . 'payamito' . '_save_before', [$this, 'option_save'], 10, 1);
    }

    public function option_save($options)
    {
        do_action("payamito_jet_save_options", $options);
    }

    public function register_settings($section)
    {
        $this->apb_status = [
            [
                "status" => "pending",
                "title" => esc_html__("Pending", PAYAMITO_JET_Text_Domain),
            ],
            [
                "status" => "processing",
                "title" => esc_html__("Processing", PAYAMITO_JET_Text_Domain),
            ],
            [
                "status" => "onhold",
                "title" => esc_html__("On hold", PAYAMITO_JET_Text_Domain),
            ],
            [
                "status" => "completed",
                "title" => esc_html__("Completed", PAYAMITO_JET_Text_Domain),
            ],
            [
                "status" => "cancelled",
                "title" => esc_html__("Cancelled", PAYAMITO_JET_Text_Domain),
            ],
            [
                "status" => "refunded",
                "title" => esc_html__("Refunded", PAYAMITO_JET_Text_Domain),
            ],
            [
                "status" => "failed",
                "title" => esc_html__("Failed", PAYAMITO_JET_Text_Domain),
            ],
        ];
        $this->meta_keys = self::get_meta_keys();
        $this->form_fields = self::get_jet_form_feilds();
        if (!defined("JET_APB_VERSION")) {
            return;
        }

        $settings = [
            'title'  => esc_html__('Jet Appointments', PAYAMITO_JET_Text_Domain),
            'fields' => [
                [
                    'id'   => 'payamito_jet_apb',
                    'type' => 'tabbed',
                    'tabs' => $this->tabs(),
                ],
            ]
        ];

        $settings = apply_filters('payamito_wp_settings', $settings);

        if (is_array($section)) {
            array_push($section, $settings);
        }

        return $section;
    }

    public function tabs()
    {
        $tabs = [];
        array_push($tabs, $this->tab_general());
        array_push($tabs, $this->tab_admin());
        array_push($tabs, $this->tab_user());
        return $tabs;
    }

    public function tab_general()
    {
        $fields = [
            'title' => esc_html__("General", PAYAMITO_JET_Text_Domain),
            'fields'    => [
                array(
                    'id'    => "jalali_converter",
                    'type'  => 'switcher',
                    'title' => esc_html__("Jalali Converter", PAYAMITO_JET_Text_Domain),
                ),
                array(
                    'id'    => "payamito_wc_compatibility",
                    'type'  => 'switcher',
                    'title' => esc_html__("Payamito Woccommerce Compatibility", PAYAMITO_JET_Text_Domain),
                )
            ]
        ];
        return $fields;
    }
    public function tab_admin()
    {
        $fields = [
            'title' => esc_html__("Admin", PAYAMITO_JET_Text_Domain),
            'fields'    => []
        ];
        foreach ($this->apb_status as $status) {

            array_push($fields['fields'], $this->set_status_field("admin", ['status' => $status['status'], 'title' => $status['title']]));
        }
        return $fields;
    }
    public function tab_user()
    {
        $fields = [
            'title' => esc_html__("User", PAYAMITO_JET_Text_Domain),
            'fields'    => []
        ];
        foreach ($this->apb_status as $status) {

            array_push($fields['fields'], $this->set_status_field("user", ['status' => $status['status'], 'title' => $status['title']]));
        }
        return $fields;
    }
    public function set_status_field($user_type, $status)
    {

        $title  = "";
        $slug   = "";
        $active = __("Enable SMS for status ", PAYAMITO_JET_Text_Domain);

        $title = $status['title'];
        $slug  = $status['status'];
        $options = $this->meta_keys;
        $options['appointmets'] = __("Appointmets", PAYAMITO_JET_Text_Domain);
        return [
            'id'         => $user_type . '_' . $slug,
            'type'       => 'accordion',
            'accordions' => [
                [
                    'title'  => esc_html__(ucfirst($title), PAYAMITO_JET_Text_Domain),
                    'fields' => [
                        [
                            'id'    => "active",
                            'title' => $active . " " . ucfirst($title),
                            'type'  => 'switcher',
                        ],
                        [
                            'id'    => "user_login",
                            'title' => esc_html__("User Login", PAYAMITO_JET_Text_Domain),
                            'type'  => 'switcher',
                        ],
                        [
                            'id'          => 'form_fields',
                            'type'        => 'select',
                            'title' => esc_html__("Form Fields", PAYAMITO_JET_Text_Domain),
                            'dependency' => ["active|user_login", '==|!=', 'true|true'],
                            'chosen' => true,
                            'options' => $this->meta_keys,
                        ],
                        [
                            'id'          => 'phone_number',
                            'type'        => 'select',
                            'title' => esc_html__("Phone Number", PAYAMITO_JET_Text_Domain),
                            'dependency' => ["active", '==', 'true'],
                            'chosen' => true,
                            'options' => $this->meta_keys,
                        ],
                        [
                            'id'          => 'date_format',
                            'type'        => 'select',
                            'title' => esc_html__("Date Format", PAYAMITO_JET_Text_Domain),
                            'dependency' => ["active", '==', 'true'],
                            'options' => [

                                "-|Y-m-d" => esc_html__(date_i18n("Y-m-d")),
                                "/|m/d/Y" => esc_html__(date_i18n("m/d/Y")),
                                "/|d/m/Y" => esc_html__(date_i18n("d/m/Y")),
                            ],
                        ],

                        [
                            'id'          => 'time_format',
                            'type'        => 'select',
                            'title' => esc_html__("Time Format", PAYAMITO_JET_Text_Domain),
                            'dependency' => ["active", '==', 'true'],
                            'options' => [
                                "g:i a" => esc_html__(date_i18n("g:i a")),
                                "g:i A" => esc_html__(date_i18n("g:i A")),
                                "H:i" => esc_html__(date_i18n("H:i")),
                            ],
                        ],
                        [
                            'id'    => "multi_appointments",
                            'title' => esc_html__("Multi appointments", PAYAMITO_JET_Text_Domain),
                            'type'  => 'switcher',
                            'dependency' => ["active", '==', 'true'],
                        ],
                        [
                            'id'          => 'multi_appointments_default',
                            'type'        => 'select',
                            'title' => esc_html__("Default", PAYAMITO_JET_Text_Domain),
                            'dependency' => ["active|multi_appointments|custom_slm", '==|==|!=', 'true|true|true'],
                            'options' => [
                                "a" => esc_html__("2022/12/01 12:00-13:00", PAYAMITO_JET_Text_Domain),
                                "b" => esc_html__("2022/12/01 start 12:00 end 13:00", PAYAMITO_JET_Text_Domain),
                                "c" => esc_html__("2022/12/01 12:00 13:00", PAYAMITO_JET_Text_Domain),
                                "d" => esc_html__("Date 2022/12/01  12:00 to 13:00", PAYAMITO_JET_Text_Domain),
                            ],
                            'desc'       => esc_html__("Format date and time are based on selected formats ", PAYAMITO_JET_Text_Domain),

                        ],
                        [
                            'id'    => "custom_slm",
                            'title' => esc_html__("Custom Format", PAYAMITO_JET_Text_Domain),
                            'type'  => 'switcher',
                            'dependency' => ["active|multi_appointments", '==|==', 'true|true'],
                        ],
                        [
                            'id'         => "slm",
                            'type'       => 'text',
                            'title' => esc_html__("Custom Format", PAYAMITO_JET_Text_Domain),
                            'class' => 'pattern_background',
                            'dependency' => ["active|multi_appointments|custom_slm", '==|==|==', 'true|true|true'],
                            'desc'       => esc_html__("Accessible variable you can use : dt=date st=start time et=end time", PAYAMITO_JET_Text_Domain),
                        ],
                        [

                            'id'         => "pattern_active",
                            'type'       => 'switcher',
                            'dependency' => ["active", '==', 'true'],
                            'title'      => payamito_dynamic_text('pattern_active_title'),
                            'desc'       => payamito_dynamic_text('pattern_active_desc'),
                            'help'       => payamito_dynamic_text('pattern_active_help'),
                            'class' => 'pattern_background'

                        ],
                        [

                            'id'         => "pattern_id",
                            'type'       => 'text',
                            'title'      => payamito_dynamic_text('pattern_ID_title'),
                            'desc'       => payamito_dynamic_text('pattern_ID_desc'),
                            'help'       => payamito_dynamic_text('pattern_ID_help'),
                            'class' => 'pattern_background',
                            'dependency' => ["active|pattern_active", '==|==', 'true|true'],
                        ],
                        [
                            'id'         => 'pattern',
                            'type'       => 'repeater',
                            'title'      => payamito_dynamic_text('pattern_Variable_title'),
                            'desc'       => payamito_dynamic_text('pattern_Variable_desc'),
                            'help'       => payamito_dynamic_text('pattern_Variable_help'),
                            'max'        => '15',
                            'class'      => "payamito-woocommerce-repeater pattern_background",
                            'dependency' => ["active|pattern_active", '==|==', 'true|true'],
                            'fields'     => [
                                [
                                    'id'          => 0,
                                    'type'        => 'select',
                                    'chosen' => true,
                                    'attributes'  => array(
                                        'data-options'      => 'dynamic',
                                    ),
                                    'placeholder' => esc_html__("Select tag", PAYAMITO_JET_Text_Domain),
                                    'options' => $options
                                ],
                                [
                                    'id'          => 1,
                                    'type'        => 'number',
                                    'placeholder' => esc_html__("Your tag", PAYAMITO_JET_Text_Domain),
                                    'default'     => '0',
                                ],
                            ]
                        ],
                        [
                            'id'         => "text",
                            'title'      => payamito_dynamic_text('send_content_title'),
                            'desc'       => payamito_dynamic_text('send_content_desc'),
                            'help'       => payamito_dynamic_text('send_content_help'),
                            'placeholder'    => esc_html__('مشتری گرامی سفارش شما با شماره {order_id} با مبلغ سفارش { price } با موفقیت انجام شد.', PAYAMITO_JET_Text_Domain),
                            'type'       => 'textarea',
                            'class' => 'pattern_background',
                            'dependency' => ["active|pattern_active", '==|!=', 'true|true'],
                        ],
                        [
                            'type'       => 'callback',
                            'dependency' => ["active|pattern_active", '==|!=', 'true|true'],
                            'function'   => [$this, 'print_tags_front'],
                            'class' => 'pattern_background',
                        ],
                    ]
                ],
            ]
        ];
    }

    public static function get_meta_keys()
    {
        global $wpdb;

        $final = array();
        $sql = "SELECT DISTINCT `meta_key` FROM `{$wpdb->usermeta}`";
        $results = $wpdb->get_results($sql, 'ARRAY_A');
        if (is_array($results)) {
            foreach ($results as  $result) {
                $final[$result['meta_key']] = $result['meta_key'];
            }
        }
        return  $final;
    }
    public static function get_jet_form_feilds()
    {
        $forms = get_posts(['post_type' => 'jet-engine-booking', 'post_status' => "publish"]);

        foreach ($forms as $form) {
            $meta = get_post_meta($form->ID, '_form_data', true);
            if ($meta === "[]") {
                continue;
            }
            $meta = json_decode(wp_unslash($meta), true);
        }
    }
}
