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
        include_once "Fondy.cls.php";
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount = round($order->getGrandTotal() * 100);

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
            'order_desc' => Mage::helper('sales')->__('Order #') . $order_id,
            'amount' => $amount,
            'currency' => $this->getConfigData('currency'),
            'server_callback_url' => $back,
            'response_url' => $back,
            'lang' => $this->getConfigData('language'),
            'sender_email' => $email
        );

        $fields['signature'] = FondyForm::getSignature($fields, $this->getConfigData('secret_key'));
        Mage::log('Request: ' . json_encode($fields), null, 'fondy.log', false);
        $params = array();
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.fondy.eu/api/checkout/url/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('request' => $fields)));
            $result = json_decode(curl_exec($ch), TRUE);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpcode != 200) {
                Mage::log('Curl code: ' . $httpcode, null, 'fondy.log', true);
                $params = array(
                    'error' => 1,
                    'message' => 'Curl error http code not 200, code is: ' . $httpcode
                );
                return $params;
            }
            if ($result['response']['response_status'] == 'failure') {
                Mage::log('Response: ' . $result['response']['error_message'], null, 'fondy.log', true);
                $params = array(
                    'error' => 1,
                    'message' => $result['response']['error_message']
                );
            } else {
                if (isset($result['response']['checkout_url'])) {
                    Mage::log('Result: ' . json_encode($result), null, 'fondy.log', false);
                    $params = array(
                        'url' => $result['response']['checkout_url'],
                        'styles' => $this->getConfigData('styles')
                    );
                } else {
                    Mage::log('Result: ' . json_encode($result), null, 'fondy.log', true);
                    $params = array(
                        'error' => 1,
                        'message' => json_encode($result)
                    );
                }
            }

        } catch (Exception $e) {
            Mage::log('Error: ' . $e, null, 'fondy.log', true);
            $params = array(
                'error' => 1,
                'message' => 'Undefined Error'
            );
        }
        return $params;
    }
}