<?php
class ModelExtensionPaymentCoinslot extends Model {
    public function install() {
        $this->load->model('setting/setting');

        $defaults = array();

        $defaults['payment_coinslot_status'] = '0';
        $defaults['payment_coinslot_x_api_key'] = '';
        $defaults['payment_coinslot_secret_key'] = '';
        $defaults['payment_coinslot_required_confirmations'] = 12;

        $this->model_setting_setting->editSetting('payment_coinslot', $defaults);
    }

    public function uninstall() {
        $this->load->model('setting/setting');

        $defaults = array();

        $defaults['payment_coinslot_status'] = '0';
        $defaults['payment_coinslot_x_api_key'] = '';
        $defaults['payment_coinslot_secret_key'] = '';
        $defaults['payment_coinslot_required_confirmations'] = 12;

        $this->model_setting_setting->editSetting('payment_coinslot', $defaults);
    }
}
