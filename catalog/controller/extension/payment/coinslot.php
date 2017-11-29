<?php
class ControllerExtensionPaymentCoinslot extends Controller {

    protected $curry_base_url = 'http://curry.atticlab.net/';

//        protected $coinslot_base_url = 'coinslot.io';
//        protected $x_api_key = '';

//        protected $coinslot_base_url = 'http://jh.demo-ico.tk';
//        protected $x_api_key = 'Lv2rc72jNnyAi1neKjJqKN';

//        protected $coinslot_base_url = 'http://192.168.1.141:4004'; // Korzun
//        protected $x_api_key = 'CLavBDxLcQuRVdoswWhnsf';

//    protected $coinslot_base_url = 'http://192.168.1.125:4000'; // Ihor
//        protected $x_api_key = '5Wt4WirHuP3NtRDtmGG75W';
//        protected $secret_key = 'YugXdOrZFlRnZPowS4Qb1FbDLiLj99PFDlmhmI1rYm8';

//    protected $ipn_url_base = 'http://192.168.1.113:8081'; // todo: remove in production
//    protected $ipn_url_base = HTTPS_SERVER; // todo: remove in production

    protected $coinslot_base_url = 'http://192.168.1.157:4000';
//        protected $x_api_key = 'Dv1eWcaXyZ8ZDEtcAMkj7S';
//        protected $secret_key = 'hUhOYu/+u21MskGmWIlr/vmO2Z8j9bpO8r3YsHZLIHU';

    protected $error_message = 'Coinslot bot sent transaction info';

	public function index() {
		$this->load->language('extension/payment/coinslot');

        $data['continue_url'] = $this->url->link('extension/payment/coinslot/checkout', '', true);

        $data['cryptocurrencies_list'] = ['ETH'];
//        $data['cryptocurrencies_list'] = $this->getCryptocurrenciesList();

		return $this->load->view('extension/payment/coinslot', $data);
	}

    protected function getCryptocurrenciesList() {

        $url = $this->coinslot_base_url . '/currencies';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $errors = curl_error($curl);

        curl_close($curl);

        if ($response == false || !empty($errors)) {
            error_log($errors);
            return false;
        }

        $response = json_decode($response, true);
        $currencies = array_map(function($i) {
            return $i['code'];
        }, $response);

        return $currencies;
    }

    public function checkout()
    {
        $this->load->language('extension/payment/coinslot');

        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1);

        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);


        $data['order_id'] = $this->session->data['order_id'];
        $data['order_total'] = $order['total'];
        $data['order_currency'] = $order['currency_code'];

        $data['order_crypto_currency'] = $_GET['cryptocurrency'];
        $data['order_crypto_total'] = $this->convertToCryptocurrency($order['total'], $order['currency_code'], $_GET['cryptocurrency']);

        try {
            $data['crypto_address'] = $this->getCryptoAddress($data['order_id'], $_GET['cryptocurrency']);
            error_log('Coinslot address - ' . $data['crypto_address']);
        } catch (\Exception $e) {
        }

        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $data['done_url'] = $this->url->link('checkout/success');

        return $this->response->setOutput($this->load->view('extension/payment/coinslot_checkout', $data));
    }

    protected function convertToCryptocurrency($amount, $currency_from, $currency_to)
    {
        if ($currency_from !== 'USD') {
            $rate = 1;

            $amount *= $rate;
        }

        $crypto_rates = $this->getCryptocurrenciesRates();

        if (empty($crypto_rates[$currency_to])) {
            throw new Exception('Can not get cryptocurrency rate for ' . $currency_to . ' from ' . $this->curry_base_url);
        }

        $amount = $amount / $crypto_rates[$currency_to];

        return $amount;
    }

    protected function getCryptocurrenciesRates()
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->curry_base_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $exchange_rates = curl_exec($curl);

        $errors = curl_error($curl);

        curl_close($curl);

        $exchange_rates = json_decode($exchange_rates, true);

        if ($exchange_rates == false || !empty($errors) || (json_last_error() !== 0)) {
            error_log($exchange_rates);
            error_log($errors);
            throw new Exception('cURL error: ' . $errors);
        }

        return $exchange_rates;
    }

    protected function getCryptoAddress($order_id, $cryptocurrency) {

        $this->load->model('setting/setting');

        $url = $this->coinslot_base_url . '/ipn';

        $data = [
            "ipn_url" => HTTPS_SERVER . '/index.php?route=extension/payment/coinslot/callback&order_id=' . $order_id,
            "confirmations" => $this->config->get('payment_coinslot_required_confirmations'),
            "currency" => $cryptocurrency
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $this->config->get('payment_coinslot_x_api_key'),
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($curl);
        $errors = curl_error($curl);

        curl_close($curl);

        if ($response == false || !empty($errors)) {
            error_log($errors . ' while trying to send coinslot ipn');
            throw new Exception('cURL error: ' . $errors); // ?
        }

        $response = json_decode($response, true);

        if (empty($response) || empty($response['address'])) {
            throw new Exception('Unable to parse response result (' . json_last_error() . ')  while trying to send coinslot ipn');
        }

        return $response['address'];
    }

    public function callback()
    {
        $this->load->model('setting/setting');

        // check sign
        $sign = $_SERVER['HTTP_SIGN'];
        $hmac = hash_hmac("sha256", implode($_POST), $this->config->get('payment_coinslot_secret_key'));

        if ($sign != $hmac) {
            $this->returnError(', but incorrect SIGN, sign - ' . $sign);
        }

        if (empty($_GET['order_id'])) {
            $this->returnError(', but order_id is absent');
        }

        $order_id = $_GET['order_id'];

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($order_id);
        $this->error_message .= ' for order_id = ' . $order_id . ', but ';

        if (empty($order)) {
            $this->returnError('can not find it in a shop DB');
        }

        try {
            $order_amount_in_cryptocurrency = $this->convertToCryptocurrency($order['total'], $order['currency_code'], $_POST['currency']);
        } catch (\Exception $e) {
            $this->returnError($e->getMessage());
        }

        if ($_POST['confirmations'] < $this->config->get('payment_coinslot_required_confirmations')) {
            $this->returnError('not enough confirmations (' . $_POST['confirmations'] . ' from ' . $this->config->get('payment_coinslot_required_confirmations') . ')');
        }

        if ($_POST['amount'] < $order_amount_in_cryptocurrency) {
            $this->returnError('but not enough money (required = ' . $order_amount_in_cryptocurrency . ' ' . $_POST['currency'] . ', had got = ' . $_POST['amount'] . ')');
        }

        $this->load->model('checkout/order');

        $this->model_checkout_order->addOrderHistory($order_id, 5);

        echo $_POST['tx_hash'];
        die;
    }

    protected function returnError($message)
    {
        $message = $this->error_message . $message;
        error_log($message);
        die;
    }
}
