<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists("Payamito_Jet_Engine_Loader")) :

    class Payamito_Jet_Engine_Loader
    {
        /**
         * The single instance of the class.
         *
         * @var Payamito_Jet_Engine_Loader
         * @since 1.0.0
         */
        protected static $_instance = null;

        public $version = '1.2.0';

        public static $slug = 'payamito_jet_engine';

        /**
         * Main Payamito_Jet_Engine_Loader Instance.
         *
         * Ensures only one instance of Payamito_Jet_Engine_Loader is loaded or can be loaded.
         *
         * @since 1.0.0
         * @static
         * @see payamito_jet()
         * @return Payamito_Jet_Engine_Loader - Main instance.
         */
        public static function get_instance()
        {
            if (is_null(self::$_instance)) {

                self::$_instance = new self();
            }
            return self::$_instance;
        }
        /**
         * Throw error on object clone
         *
         * The whole idea of the singleton design pattern is that there is a single
         * object therefore, we don't want the object to be cloned.
         *
         * @since 1.0.0
         * @return void
         */
        public function __clone()
        {
            // Cloning instances of the class is forbidden
            _doing_it_wrong(__FUNCTION__, __('Something went wrong.', PAYAMITO_JET_Text_Domain), '1.0.0');
        }

        /**
         * Disable unserializing of the class
         *
         * @since 1.0.0
         * @return void
         */
        public function __wakeup()
        {
            // Unserializing instances of the class is forbidden
            _doing_it_wrong(__FUNCTION__, __('Something went wrong.', PAYAMITO_JET_Text_Domain), '1.0.0');
        }

        public function __construct()
        {
            $this->define_constant();

            add_action('after_setup_theme', array($this, 'framework_loader'), -10);
            add_action("after_setup_theme", [$this, 'load_core'], -99);
            add_action("payamito_jet_save_options", ['Payamito_Jet_Prepare', 'prepare_options'], 10, 2);

            $this->load_files();
            add_action("jet-engine/init", [$this, 'init']);
            // Register activation and deactivation hook.
            $this->install();
        }
        public function load_files()
        {
            require_once __DIR__ . '/class-jet-prepare.php';
            require_once __DIR__ . '/class-jet-send.php';
            require_once __DIR__ . '/class-jet-form.php';
            require_once __DIR__ . '/class-jet-base.php';
            require_once __DIR__ . '/class-jet-admin.php';
            require_once __DIR__ . '/class-jet-user.php';
            require_once __DIR__ . '/class-jet-otp.php';
            require_once __DIR__ . '/class-jet-wc-integration.php';
            require_once __DIR__ . '/class-jet-updater.php';
            require_once __DIR__ . '/class-jet-appointmets.php';
            if(is_admin()){
            require_once __DIR__ . '/admin/class-jet-options.php';
            }
        }

        public function framework_loader()
        {
            require_once __DIR__ . '/class-jet-form-meta-box.php';
            
        }

        public function load_core()
        {
            require_once __DIR__ . '/functions.php';
            if(is_admin()){
              //  new Payamito_Jet_Options;
             }
            require_once   payamito_jet_load_core() . 'payamito.php';
        }
        public function define_constant()
        {
            if (!defined("PAYAMITO_JET_CORE_DIR")) {
                define('PAYAMITO_JET_CORE_DIR', __DIR__ . '/core/payamito-core/');
            }

            if (!defined("PAYAMITO_JET_CORE_VERSION")) {
                define('PAYAMITO_JET_CORE_VERSION', '2.0.0');
            }
            if (!defined("PAYAMITO_JET_VERSION")) {
                define('PAYAMITO_JET_VERSION', $this->version);
            }
            if (!defined("PAYAMITO_JET_Text_Domain")) {
                define('PAYAMITO_JET_Text_Domain', 'payamito-jet-engine');
            }
            if (!defined("PAYAMITO_JET_PREFIX")) {
                define('PAYAMITO_JET_PREFIX', 'payamito_jet');
            }
            if (!defined('PAYAMITO_JET_URL')) {

                define('PAYAMITO_JET_URL',  plugin_dir_url(PAYAMITO_JET_ENGINE_PLUGIN_FILE));
            }
        }
        public static function is_jet_engine_installed()
        {
            return class_exists("Jet_Engine");
        }
        public function install()
        {
            register_activation_hook(PAYAMITO_JET_ENGINE_PLUGIN_FILE, ['Payamito_Jet_Engine_Install', 'active']);
            register_deactivation_hook(PAYAMITO_JET_ENGINE_PLUGIN_FILE, ['Payamito_Jet_Engine_Install', 'deactive']);
        }

        public function dependency($plugin)
        {
            if ($plugin === 'Jet_Engine') {
                add_action('admin_notices', '_payamito_jet_no_intalled_jet_engine');
            }
        }
        public function init()
        {
            if (!$this->is_jet_engine_installed()) return $this->dependency('Jet_Engine');
            Payamito_Jet_Engine_Metabox::get_instance();
            Payamito_Jet_Engine_Form::get_instance();
            Payamito_Jet_Engine_Updater::init();
            new  Payamito_Jet_Appointmet();
           
        }
        /**
         * Loads the translation files.
         *
         * @since 1.0.0
         * @access public
         * @return void
         */
        public function lang()
        {
            load_plugin_textdomain(PAYAMITO_JET_Text_Domain, false, dirname(plugin_basename(PAYAMITO_JET_ENGINE_PLUGIN_FILE)) . '/languages');
        }
    }
endif;
