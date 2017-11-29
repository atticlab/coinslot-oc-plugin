<?php
class ModelExtensionPaymentCoinslot extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/coinslot');

        $method_data = array(
            'code'       => 'coinslot',
            'title'      => $this->language->get('text_title'),
            'terms'      => '',
            'sort_order' => 99 // todo get from admin panel
        );

		return $method_data;
	}
}
