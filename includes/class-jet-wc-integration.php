<?php

class Payamito_Jet_Wc_Integration
{

    public function __construct()
    {
        add_action('woocommerce_order_status_changed', array($this, 'integration'), 1);
        add_action('woocommerce_new_order', array($this, 'integration'), 1);
    }

    public function integration()
    {

        $this->remove_payamito_wc_action();
    }

    public function remove_payamito_wc_action()
    {

        payamito_jet_remove_class_filter("woocommerce_order_status_changed", "Payamito\Woocommerce\P_Woocommerce", "order_status_changed", 99);
        payamito_jet_remove_class_filter("woocommerce_new_order", "Payamito\Woocommerce\P_Woocommerce", "new_order", 99);
    }

    public static function init($form, $settings, $data)
    {
        new Payamito_Jet_Wc_Integration($form, $settings, $data);
    }
}
