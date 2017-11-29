<?php
class ControllerExtensionPaymentCoinslot extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/coinslot');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_coinslot', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['required_confirmations'])) {
            $data['error_required_confirmations'] = $this->error['required_confirmations'];
        } else {
            $data['error_required_confirmations'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/coinslot', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['action'] = $this->url->link('extension/payment/coinslot', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } else {
            $data['status'] = $this->config->get('payment_coinslot_status');
        }

        if (isset($this->request->post['x_api_key'])) {
            $data['x_api_key'] = $this->request->post['x_api_key'];
        } else {
            $data['x_api_key'] = $this->config->get('payment_coinslot_x_api_key');
        }

        if (isset($this->request->post['secret_key'])) {
            $data['secret_key'] = $this->request->post['secret_key'];
        } else {
            $data['secret_key'] = $this->config->get('payment_coinslot_secret_key');
        }

        if (isset($this->request->post['required_confirmations'])) {
            $data['required_confirmations'] = $this->request->post['required_confirmations'];
        } else {
            $data['required_confirmations'] = $this->config->get('payment_coinslot_required_confirmations');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/coinslot', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/coinslot')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model('extension/payment/coinslot');

        $this->model_extension_payment_coinslot->install();
    }

    public function uninstall() {
        $this->load->model('extension/payment/coinslot');

        $this->model_extension_payment_coinslot->uninstall();
    }
}
