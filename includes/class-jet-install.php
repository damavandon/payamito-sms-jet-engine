<?php
// ═══════════════════════════ :هشدار: ═══════════════════════════

// ‫ تمامی حقوق مادی و معنوی این افزونه متعلق به سایت پیامیتو به آدرس payamito.com می باشد
// ‫ و هرگونه تغییر در سورس یا استفاده برای درگاهی غیراز پیامیتو ،
// ‫ قانوناً و شرعاً غیرمجاز و دارای پیگرد قانونی می باشد.

// © 2022 Payamito.com, Kian Dev Co. All rights reserved.

// ════════════════════════════════════════════════════════════════


?><?php
if (!class_exists("Payamito_Jet_Engine_Install")) {
    class  Payamito_Jet_Engine_Install
    {
        public static function active()
        {
            self::install();
            require_once payamito_jet_load_core() . '/includes/class-payamito-activator.php';
            Payamito_Activator::activate();
            payamito_jet_insert_table_column("pjet_formid");
            payamito_jet_insert_table_column("pjet_phone");
        }
        public static function deactive(){}

        public static $core_version;
        public static $__FILE__;
        public static $core_path;
        public static function install($core_version = PAYAMITO_JET_CORE_VERSION, $__FILE__ = PAYAMITO_JET_ENGINE_PLUGIN_FILE, $core_path = PAYAMITO_JET_CORE_DIR)
        {
            if (!is_blog_installed()) {
                wp_die('WordPress is not already installed');
            }
            self::$core_version = $core_version;
            self::$__FILE__ = $__FILE__;
            self::$core_path = $core_path;
            self::set_core_version();
        }

        private static function set_core_version()
        {

            $core_version = get_option("payamito_core_version");
            $dir_name = self::get_fil_name(__DIR__);
            $file_name = basename(self::$__FILE__);

            $update = [
                'version' => self::$core_version,
                'absolute_path' => $dir_name . '/' . $file_name,
                'core_path' => self::$core_path,
            ];

            if ($core_version === false) {
                update_option("payamito_core_version", serialize($update));
            } else {
                $self_version = self::$core_version;
                $other_version = unserialize($core_version)['version'];

                if ($self_version > $other_version) {
                    update_option("payamito_core_version", serialize($update));
                }
            }
        }

        private static function get_fil_name($__DIR__)
        {
            $dir_name = basename(dirname($__DIR__, 1));

            if ($dir_name === 'plugins') {
                $dir_name = dirname(plugin_basename(__FILE__));
            }
            return $dir_name;
        }
    }
}
