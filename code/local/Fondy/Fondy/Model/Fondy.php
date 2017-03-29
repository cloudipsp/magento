<?php

class Fondy_Fondy_Model_Fondy extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'Fondy';
    protected $_formBlockType = 'Fondy/form';

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('Fondy/redirect', array('_secure' => true));
    }

    public function getQuote()
    {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        return $order;
    }

    public function getFormFields()
    {  
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount = round($order->getGrandTotal(), 2);  
		
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $checkout = Mage::getSingleton('checkout/session')->getCustomer();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $email = $customer->getEmail();

        $email = isset($email) ? $email : $quote->getBillingAddress()->getEmail();
        $email = isset($email) ? $email : $order->getCustomerEmail();
        $back = $this->getConfigData('back_ref');
		$currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
		$currency_accepted = array("USD", "UAH", "GBP", "RUB","EUR");
		if (in_array($currency_code,$currency_accepted)){
			$currency = $currency_code;
		}else{
			$currency = $this->getConfigData('currency');
		}
		//default merchant
        $data = array(				
            'order_id' => $order_id .'#'. time(),
            'merchant_id' => $this->getConfigData('merchant'),
            'order_desc' => Mage::helper('sales')->__('Order #') . $order_id,
            'amount' => round($amount*100),
            'currency' => $currency,
            'server_callback_url' => $back,
            'response_url' => $back,
            'lang' => $this->getConfigData('language'),
            'sender_email' => $email
			);
		// add merchant info by product
		$items = $order->getAllVisibleItems();
		$second_price = 0;
		foreach ($items as $i) {
			$price = $i->getRowTotalInclTax();
			$quantity_second_merchant = Mage::getResourceModel('catalog/product')->getAttributeRawValue($i->getProductId(), 'ВНаличииMerchant');
			if(empty($quantity) or $quantity == ''){
				$attr = Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('frontend_label', 'ВНаличииMerchant');
				$attribute_code = $attr->getData('attribute_code')[0]['attribute_code'];
				$quantity_second_merchant = Mage::getResourceModel('catalog/product')->getAttributeRawValue($i->getProductId(), $attribute_code);
			}

			// second merchant id
			$new_price = round($price * 100);
			if (isset($quantity_second_merchant) and $quantity_second_merchant > 0) {
				$second_price += $new_price;
				$data['receiver'] = [
					"requisites" => array(
						"amount" => $second_price,
						"merchant_id" => $this->getConfigData('merchant_second')
					),
					"type" => "merchant"];
			}
		}
		$fields = [
		"version" => "2.0",
		"data" => base64_encode(json_encode(array('order' => $data))),
		"signature" => sha1($this->getConfigData('secret_key') .'|'. base64_encode(json_encode(array('order' => $data))))
		]; 
				
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.fondy.eu/api/checkout/url/');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('request'=>$fields)));
		$result = curl_exec($ch);
		$out = json_decode($result,TRUE);
		$url = base64_decode($out['response']['data']);
		
	
		if (empty($url)){
			Mage::throwException('An error has occurred request. ' . $out['response']['error_message'] . '. Request id: ' . $out['response']['request_id']);
		}
		
        $params = array(
            'url' => json_decode($url,TRUE)['order']['checkout_url'],
			'data' => $data
        );
	
        return $params;
    }

}


