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
        $checkout = Mage::getSingleton('checkout/session')->getCustomer();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $email = $customer->getEmail();
        $email = isset($email) ? $email : $quote->getBillingAddress()->getEmail();
        $email = isset($email) ? $email : $order->getCustomerEmail();

        //Try to get merchant by zip
        $zipCode = $order->getShippingAddress()->getPostcode();
        $merchant_info = $this->getMerchantByZip($zipCode, unserialize($this->getConfigData('merchants')));


        $callback_url = Mage::getUrl('Fondy/response', array('_secure' => true));
        $response_url = $this->getConfigData('back_ref');

        $fields = array(
            'order_id' => $order_id . FondyForm::ORDER_SEPARATOR . time(),
            'merchant_id' => $merchant_info['merchant_id'],
            'order_desc' => Mage::helper('sales')->__('Order #') . $order_id,
            'amount' => $amount,
            'merchant_data' => $zipCode,
            'currency' => $this->getConfigData('currency'),
            'server_callback_url' => $callback_url,
            'response_url' => !empty($response_url) ? $response_url : Mage::getUrl('checkout/onepage/success', array('_secure' => true)),
            'lang' => $this->getConfigData('language'),
            'sender_email' => $email
        );

        $fields['signature'] = FondyForm::getSignature($fields, $merchant_info['secret_key']);

        $params = array(
            'button' => $this->getButton(),
            'fields' => $fields,
        );
        return $params;
    }

    public function getButton()
    {
        $button = "<div style='position:absolute; top:50%; left:50%; margin:-40px 0px 0px -60px; '>" .
            "</div>" .
            "<script type=\"text/javascript\">
            setTimeout( subform, 200 );
            function subform(){ document.getElementById('FondyForm').submit(); }
            </script>";

        return $button;
    }

    public function getMerchantByZip($zip, array $merchants)
    {
        $mid = '';
        $secret = '';
        foreach ($merchants as $data) {
            if (strpos($data['city_index'], '-') !== false) {
                foreach (explode('-', $data['city_index']) as $index) {
                    if ($index == $zip) {
                        $mid = $data['merchant_id'];
                        $secret = $data['secret_key'];
                        break;
                    }
                }
            }
            if ($data['city_index'] == $zip) {
                $mid = $data['merchant_id'];
                $secret = $data['secret_key'];
                break;
            }
        }
        if (empty($mid)) {
            $mid = $this->getConfigData('merchant');
        }
        if (empty($secret)) {
            $secret = $this->getConfigData('secret_key');
        }

        return array(
            'merchant_id' => $mid,
            'secret_key' => $secret
        );
    }
}


