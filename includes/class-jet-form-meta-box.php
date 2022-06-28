<?php
if (!class_exists("Payamito_Jet_Engine_Metabox")) :
    class Payamito_Jet_Engine_Metabox
    {
        private $box;
        public $prefix = "payamito_jet_metabox";
        public $general_slug = "general";
        public $otp_slug = "otp";
        public $fields = [];
        public $user_slug = "user";
        public $admin_slug = "admin";
        public $apb_field_added = false;
        public $post_id;

        public $apb_status;
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
        public static function get_general_slug()
        {
            return "general";
        }
        public static function get_otp_slug()
        {
            return "otp";
        }
        public static function get_user_slug()
        {
            return "user";
        }
        public static function get_admin_slug()
        {
            return "admin";
        }
        public function __construct()
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
            $this->register_box();
            add_action("kianfr_{$this->prefix}_save_after", [$this, 'save'], 10, 1);
        }
        public function save($settings)
        {
            do_action("payamito_jet_save_options", $settings, $this);
        }

        public function register_box()
        {
            $post_id = null;
            if (!isset($_GET['post'])) {
                if (isset($_POST['post_ID'])) {
                    $post_id = sanitize_text_field($_POST['post_ID']);
                } else {
                    return;
                }
                if (isset($_GET['post'])) {
                    $post_id = sanitize_text_field($_GET['post']);
                }
            }
           
            $this->post_id = $post_id;
            $data = get_post_meta($post_id, "_form_data", true);
            $data = json_decode(wp_unslash($data), true);
            $fields = [];
            if (is_array($data)) {
                foreach ($data as $field) {
                    $settings = $field['settings'];
                    if ($settings['type'] === 'text' || $settings['type'] === 'number') {
                        $fields[$settings['name']] = $settings['label'];
                    }
                }
                $this->fields = $fields;
            }

            // Control core classes for avoid errors
            if (class_exists('KIANFR')) {

                $prefix = $this->prefix;
                KIANFR::createMetabox($prefix, array(
                    'title'     => esc_html__("Payamito", PAYAMITO_JET_Text_Domain),
                    'post_type' => 'jet-engine-booking',
                    'theme' => 'light',
                ));
                $this->general_fields();

                $this->otp_fields();

                $this->user_fields();

                $this->admin_fields();
            }
        }
        public function general_fields()
        {
            $title = esc_html__("General", PAYAMITO_JET_Text_Domain);
            $fields = [
                array(
                    'id'    => "{$this->general_slug}_jalali_converter",
                    'type'  => 'switcher',
                    'title' => esc_html__("Jalali Converter", PAYAMITO_JET_Text_Domain),
                ),
            ];
            if (defined("JET_APB_VERSION")) {
                array_push($fields, array(
                    'id'    => "{$this->general_slug}_payamito_wc_compatibility",
                    'type'  => 'switcher',
                    'title' => esc_html__("Payamito Woccommerce Compatibility", PAYAMITO_JET_Text_Domain),
                ));
            }
            KIANFR::createSection($this->prefix, array(
                'title'  => $title,
                'fields' => $fields
            ));
        }
        public function otp_fields()
        {
            $fields = [];
            $default = esc_html__('confirm OTP code is OTP', PAYAMITO_JET_Text_Domain);
            $desc = esc_html__('Use OTP site_title tags', PAYAMITO_JET_Text_Domain);
            $pattern_options = [
                "OTP" => esc_html__("OTP", PAYAMITO_JET_Text_Domain),
                "site_title" => esc_html__("Website Title", PAYAMITO_JET_Text_Domain)
            ];
            $title = esc_html__("Verification", PAYAMITO_JET_Text_Domain);

            $this->add_section($this->otp_slug, $fields, ["public" => $this->fields, "pattern" => $pattern_options], $default, $desc, $title);
        }

        public function add_section($slug, $fields, $options, $text_default, $text_desc, $title)
        {
            $fields = $this->public_fields($slug, $fields, $options['public']);
            if ($slug === 'otp') {
                $fields = $this->otp_custom_fields($slug, $fields);
            }
            $fields = $this->pattern_fileds($slug, $fields, $options['pattern']);
            $fields = $this->text_fields($slug, $fields, $text_default, $text_desc);

            if (defined("JET_APB_VERSION")) {
                if ($slug === 'admin' || $slug === 'user') {

                    array_push($fields, array(
                        'type'    => 'subheading',
                        'content' => esc_html__("Appointmets", PAYAMITO_JET_Text_Domain),
                        'dependency' => [$slug . "_active", '==', 'true'],
                    ));

                    foreach ($this->apb_status as $status) {

                        array_push($fields, $this->set_status_field($slug, ['status' => $status['status'], 'title' => $status['title']]));
                    }
                    $this->apb_field_added = true;
                }
            }
            KIANFR::createSection($this->prefix, array(
                'title'  => $title,
                'fields' => $fields
            ));
        }


        public function set_status_field($user_type, $status)
        {

            $title  = "";
            $slug   = "";
            $active = __("Enable SMS for status ", PAYAMITO_JET_Text_Domain);

            $title = $status['title'];
            $slug  = $status['status'];
            return [

                'id'         => $user_type . '_' . $slug,
                'dependency' => [$user_type . "_active", '==', 'true'],
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
                                'class'      => "pattern_background",
                                'dependency' => ["active|pattern_active", '==|==', 'true|true'],
                                'button_title' => esc_html__("Add Pattern", PAYAMITO_JET_Text_Domain),
                                'fields'     => [
                                    [
                                        'id'          => 0,
                                        'type'        => 'select',
                                        'chosen' => true,
                                        'settings' => [
                                            'width' => '100%'
                                        ],
                                        'options' => [], 'options' => [
                                            esc_html__("Appointmets", PAYAMITO_JET_Text_Domain) => [
                                                "ID" => esc_html__("ID", PAYAMITO_JET_Text_Domain),
                                                "service" => esc_html__("Service", PAYAMITO_JET_Text_Domain),
                                                "provider" => esc_html__("Provider", PAYAMITO_JET_Text_Domain),
                                                "price" => esc_html__("Price", PAYAMITO_JET_Text_Domain),
                                                "date" => esc_html__("Date", PAYAMITO_JET_Text_Domain),
                                                "slog" => esc_html__("Start Time", PAYAMITO_JET_Text_Domain),
                                                "slog_end" => esc_html__("End Time", PAYAMITO_JET_Text_Domain),
                                            ],
                                            esc_html__("User Meta", PAYAMITO_JET_Text_Domain) => self::get_meta_keys()
                                        ],
                                    ],
                                    [
                                        'id'         => 1,
                                        'type'       => 'number',
                                        'placeholder' => esc_html__("User tag", PAYAMITO_JET_Text_Domain),
                                    ],

                                ]
                            ],

                            [
                                'id'          => 'text_tag',
                                'type'        => 'select',
                                'title' => esc_html__("Tags", PAYAMITO_JET_Text_Domain),
                                'dependency' => ["active|pattern_active", '==|!=', 'true|true'],
                                'chosen' => true,
                                'settings' => [
                                    'width' => '100%'
                                ],
                                'attributes'  => array(
                                    'data-copy-user-meta' => 'copy',
                                    'data-copy-texarea' => $id = rand(9999, 99999),
                                ),
                                'options' => [
                                    esc_html__("Appointmets", PAYAMITO_JET_Text_Domain) => [
                                        "ID" => esc_html__("ID", PAYAMITO_JET_Text_Domain),
                                        "service" => esc_html__("Service", PAYAMITO_JET_Text_Domain),
                                        "provider" => esc_html__("Provider", PAYAMITO_JET_Text_Domain),
                                        "price" => esc_html__("Price", PAYAMITO_JET_Text_Domain),
                                        "date" => esc_html__("Date", PAYAMITO_JET_Text_Domain),
                                        "slot" => esc_html__("Start Time", PAYAMITO_JET_Text_Domain),
                                        "slot_end" => esc_html__("End Time", PAYAMITO_JET_Text_Domain),
                                    ],
                                    esc_html__("User Meta", PAYAMITO_JET_Text_Domain) => self::get_meta_keys()
                                ],
                            ],
                            [
                                'id'         => "text",
                                'title'      => payamito_dynamic_text('send_content_title'),
                                'type'       => 'textarea',
                                'attributes'  => array(
                                    'data-texarea' => $id,
                                ),
                                'dependency' => ["active|pattern_active", '==|!=', 'true|true'],
                            ],

                        ]
                    ],
                ]
            ];
        }

        public function user_fields()
        {
            $fields = [];
            $default = esc_html__('Welcom to my website filed id', PAYAMITO_JET_Text_Domain);
            $desc = esc_html__('Welcom to my website filed id', PAYAMITO_JET_Text_Domain);
            $title = esc_html__("User", PAYAMITO_JET_Text_Domain);

            $this->add_section($this->user_slug, $fields, ["public" => $this->fields, "pattern" => $this->fields], $default, $desc, $title);
        }
        public function admin_fields()
        {
            $fields = [];
            $default = esc_html__('Welcom to my website filed id', PAYAMITO_JET_Text_Domain);
            $desc = esc_html__('Welcom to my website filed id', PAYAMITO_JET_Text_Domain);
            $title = esc_html__("Admin", PAYAMITO_JET_Text_Domain);
            $this->add_section($this->admin_slug, $fields, ["public" => $this->fields, "pattern" => $this->fields], $default, $desc, $title);
        }
        public function otp_custom_fields($slug, $fields)
        {
            $active_id = "{$slug}_active";
            $default = [
                array(
                    'id'    =>  "{$slug}_count",
                    'title' => esc_html__("Count", PAYAMITO_JET_Text_Domain),
                    'type'  => 'number',
                    'default' => '4',
                    'dependency' => array($active_id, '==', 'true'),
                ),
                array(
                    'id'    =>  "{$slug}_resend",
                    'title' => esc_html__("Resend", PAYAMITO_JET_Text_Domain),
                    'type'  => 'number',
                    'default' => '60',
                    'dependency' => array($active_id, '==', 'true'),
                ),
                array(
                    'id'    =>  "{$slug}_lable",
                    'title' => esc_html__("OTP Lable", PAYAMITO_JET_Text_Domain),
                    'type'  => 'text',
                    'default' => 'OTP',
                    'dependency' => array($active_id, '==', 'true'),
                ),
                array(
                    'id'    =>  "{$slug}_placeholder",
                    'title' => esc_html__("OTP Placeholder", PAYAMITO_JET_Text_Domain),
                    'type'  => 'text',
                    'default' => 'OTP',
                    'dependency' => array($active_id, '==', 'true'),
                ),
                array(
                    'id'    =>  "{$slug}_resend_button",
                    'title' => esc_html__("Text Resend Button", PAYAMITO_JET_Text_Domain),
                    'type'  => 'text',
                    'default' => 'Resend',
                    'dependency' => array($active_id, '==', 'true'),
                ),

            ];
            foreach ($default as $field) {
                array_push($fields, $field);
            }
            return $fields;
        }
        public  function public_fields($slug, $fields, $options)
        {
            if (count($options) === 0) {
                array_push($options, ' ');
            }

            $active_id = "{$slug}_active";

            $default = [
                array(
                    'id'    => $active_id,
                    'type'  => 'switcher',
                    'title' => esc_html__("Active", PAYAMITO_JET_Text_Domain),
                ),
                array(
                    'id'          => "{$slug}_field",
                    'type'        => 'select',
                    'title' => esc_html__("Field", PAYAMITO_JET_Text_Domain),
                    'options'     => $options,
                    'class' => 'payamito-jet-field',
                    'attributes'  => array(
                        'data-options'      => 'dynamic',
                        'data-field'      => 'field',
                    ),
                    'dependency' => array($active_id, '==', 'true'),
                ),
            ];
            if ($slug === $this->admin_slug) {
                $default = [
                    array(
                        'id'    => $active_id,
                        'type'  => 'switcher',
                        'title' => esc_html__("Active", PAYAMITO_JET_Text_Domain),
                    ),
                    array(
                        'id'     => "{$slug}_phone",
                        'type'   => 'repeater',
                        'title'      => esc_html__("Phone Number", PAYAMITO_JET_Text_Domain),
                        'dependency' => array($active_id, '==', 'true'),
                        'class' => 'pattern_background',
                        'fields' => array(
                            array(
                                'id'    => 'phone_number',
                                'type'  => 'number',
                                'placeholder' =>  esc_html__("09120000000", PAYAMITO_JET_Text_Domain),
                                'class' => 'pattern_background',
                            ),
                        )
                    ),
                ];
            }
            foreach ($default as $field) {
                array_push($fields, $field);
            }
            return $fields;
        }
        public function pattern_fileds($slug, $fields, $options)
        {
            if (count($options) === 0) {
                array_push($options, ' ');
            }
            $option_type = "dynamic";
            if ($slug === $this->otp_slug) {
                $option_type = "static";
            }

            $active_id = "{$slug}_active";
            $pattern_active = "{$slug}_pattern_active";

            $dependency_1 =  array("{$active_id}|{$pattern_active}", '==|==', 'true|true');

            $default = [
                array(
                    'id'    => $pattern_active,
                    'type'  => 'switcher',
                    'title' => esc_html__("Pattern Active", PAYAMITO_JET_Text_Domain),
                    'dependency' => array($active_id, '==', 'true'),
                ),
                array(
                    'id'    => "{$slug}_pattern_id",
                    'type'  => 'number',
                    'title' => esc_html__("Pattern ID", PAYAMITO_JET_Text_Domain),
                    'dependency' => $dependency_1,
                ),
                array(
                    'id'     => "{$slug}_pattern",
                    'type'   => 'repeater',
                    'title'      => payamito_dynamic_text('pattern_Variable_title'),
                    'desc'       => payamito_dynamic_text('pattern_Variable_desc'),
                    'help'       => payamito_dynamic_text('pattern_Variable_help'),
                    'dependency' => $dependency_1,
                    'class' => 'pattern_background',
                    'fields' => array(
                        array(
                            'id'   => 0,
                            'placeholder' =>  esc_html__("Tags", PAYAMITO_JET_Text_Domain),
                            'class' => 'pattern_background ',
                            'type' => 'select',
                            'attributes'  => array(
                                'data-options'      => $option_type,
                            ),
                            'options' => $options
                        ),
                        array(
                            'id'    => 1,
                            'type'  => 'number',
                            'placeholder' =>  esc_html__("Your tag", PAYAMITO_JET_Text_Domain),
                            'class' => 'pattern_background',
                            'default' => '0',
                        ),
                    )
                )
            ];
            foreach ($default as $field) {
                array_push($fields, $field);
            }
            return $fields;
        }

        public function text_fields($slug, $fields, $d, $desc)
        {
            $active_id = "{$slug}_active";
            $pattern_active = "{$slug}_pattern_active";
            $dependency_2 =  array("{$active_id}|{$pattern_active}", '==|!=', 'true|true');

            $default = [array(
                'id'     => "{$slug}_text",
                'title'      => payamito_dynamic_text('send_content_title'),
                'desc'       => payamito_dynamic_text('send_content_desc'),
                'help'       => payamito_dynamic_text('send_content_help'),
                'default' => $d,
                'class' => 'pattern_background',
                'type' => 'textarea',
                'dependency' => $dependency_2,
                'desc' => $desc,
            )];
            foreach ($default as $field) {
                array_push($fields, $field);
            }
            return $fields;
        }
        public static function get_meta_keys()
        {
            global $wpdb;

            $final = array();
            $sql = "SELECT DISTINCT `meta_key` FROM `{$wpdb->usermeta}`";
            $results = $wpdb->get_results($sql, 'ARRAY_A');
            if (is_array($results)) {
                foreach ($results as  $result) {
                    $final[$result['meta_key']] = ucfirst(str_replace("_", " ", $result['meta_key']));
                }
            }
            return  $final;
        }
    }
endif;
