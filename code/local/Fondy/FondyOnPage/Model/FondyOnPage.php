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
    {   include_once "Fondy.cls.php";
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
        $fields = array(
            'order_id' => $order_id . FondyForm::ORDER_SEPARATOR . time(),
            'merchant_id' => $this->getConfigData('merchant'),
            'order_desc' => 'Order pay'.$order_id,
            'amount' => $amount,
            'currency' => $this->getConfigData('currency'),
            'server_callback_url' => $back,
            'response_url' => $back,
            'lang' => $this->getConfigData('language'),
            'sender_email' => $email
        );

        $fields['signature'] = FondyForm::getSignature($fields, $this->getConfigData('secret_key'));

        $params = array(

            'fields' => $fields
        );
        return $params;
    }


}


