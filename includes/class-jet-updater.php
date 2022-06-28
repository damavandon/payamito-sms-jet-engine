<?php
// ═══════════════════════════ :هشدار: ═══════════════════════════

// ‫ تمامی حقوق مادی و معنوی این افزونه متعلق به سایت پیامیتو به آدرس payamito.com می باشد
// ‫ و هرگونه تغییر در سورس یا استفاده برای درگاهی غیراز پیامیتو ،
// ‫ قانوناً و شرعاً غیرمجاز و دارای پیگرد قانونی می باشد.

// © 2022 Payamito.com, Kian Dev Co. All rights reserved.

// ════════════════════════════════════════════════════════════════


?><?php
if (!class_exists('Payamito_Jet_Engine_Updater')) {
    class Payamito_Jet_Engine_Updater
    {
        public static function init()
        {
            if (!class_exists("Puc_v4_Factory")) {
                require_once __DIR__ . '/lib/plugin-update-checker-master/plugin-update-checker.php';
            }
            self::update_cheker();
        }

        public static function update_cheker()
        {
            
            $server = 'http://updater.payamito.com/?action=download&slug=payamito-sms-jet-engine';
            $bootstrap_path = PAYAMITO_JET_ENGINE_PLUGIN_FILE;
            $slug = 'payamito-sms-jet-engine';

            try {
                Puc_v4_Factory::buildUpdateChecker($server, $bootstrap_path, $slug);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }
}
