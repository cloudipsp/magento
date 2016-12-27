<?php

class Fondy_FondyOnPage_Model_FondyOnPage extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'FondyOnPage';
    protected $_formBlockType = 'FondyOnPage/form';

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
 
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('FondyOnPage/checkout', array('_secure' => true));
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
        $back = Mage::getUrl('FondyOnPage/response', array('_secure' => true));
        $data = array(				
            'order_id' => $order_id .'#'. time(),
            'merchant_id' => $this->getConfigData('merchant'),
            'order_desc' => 'Оплата заказа: ' . $order_id,
            'amount' => round($amount*100),
            'currency' => $this->getConfigData('currency'),
            'server_callback_url' => $back,
            'response_url' => $back,
            'lang' => $this->getConfigData('language'),
            'sender_email' => $email
			);
		// add merchant info by product
		$items = $order->getAllVisibleItems();
		foreach($items as $i){		
				$price = Mage::helper('tax')->getPrice($i->getProduct(), $i->getProduct()->getFinalPrice(), true); 
				$merchant =  Mage::getResourceModel('catalog/product')->getAttributeRawValue($i->getProductId(), 'merchantid');
				// default merchant id
				if (empty($merchant))
					$merchant = $this->getConfigData('merchant');
				$new_price = round($price * 100 * $i->getQtyOrdered()); //product price * pruduct quantity
				if ($order->getTotalItemCount() > 1){
					$data['receiver'][] = [
					"requisites" => array(
								"amount" => $new_price,
								"merchant_id" => $merchant
									),
					"type" => "merchant"];
				}elseif($order->getTotalItemCount() == 1){
					$data['receiver'] = [
						"requisites" => array(
									"amount" => $new_price,
									"merchant_id" => $merchant
										),
						"type" => "merchant"];				
				}
		}
				/*$data['receiver'][] = array(
				"requisites" => array(
					 "amount" => 100,
					 "merchant_id" =>  500001
					 ),
				"type" => "merchant");
				$data['receiver'][] = array(
				"requisites" => array(
					 "amount" => 100,
					 "merchant_id" =>  600001
					 ),
				"type" => "merchant");*/

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
			'styles'=> $this->getConfigData('styles'),
			'data' => $data
        );
	
        return $params;
    }


}


