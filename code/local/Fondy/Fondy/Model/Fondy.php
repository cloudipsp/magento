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
        $amount = round($order->getGrandTotal() * 100, 2);

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $email = $customer->getEmail();
        $email = isset($email) ? $email : $quote->getBillingAddress()->getEmail();
        $email = isset($email) ? $email : $order->getCustomerEmail();
		
		$callback_url = Mage::getUrl('Fondy/response', array('_secure' => true, '_query' => 'callback=1'));
        $response_url = $this->getConfigData('back_ref');

        $fields = array(
            'order_id' => $order_id . FondyForm::ORDER_SEPARATOR . time(),
            'merchant_id' => $this->getConfigData('merchant'),
            'order_desc' => Mage::helper('sales')->__('Order #') . $order_id,
            'amount' => $amount,
            'currency' => $this->getConfigData('currency'),
            'server_callback_url' => $callback_url,
            'response_url' => !empty($response_url) ? $response_url : Mage::getUrl('checkout/onepage/success', array('_secure' => true)),
            'lang' => $this->getConfigData('language'),
            'sender_email' => $email
        );

        $fields['signature'] = FondyForm::getSignature($fields, $this->getConfigData('secret_key'));

        $params = array(
            'button' => $this->getButton(),
            'fields' => $fields,
        );
        return $params;
    }

    function getButton()
    {
        $button = "<div style='position:absolute; top:50%; left:50%; margin:-40px 0px 0px -60px; '>" .
            #"<div><img src='http://www.payu.ua/sites/default/files/logo-payu.png' width='120px' style='margin:5px 5px;'></div>".
            "</div>" .
            "<script type=\"text/javascript\">
            setTimeout( subform, 200 );
            function subform(){ document.getElementById('FondyForm').submit(); }
            </script>";

        return $button;
    }

}


