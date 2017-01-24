<?php
/*
 *
 * @category   Community
 * @package    Fondy_Fondy
 * @copyright  http://fondy.eu
 * @license    Open Software License (OSL 3.0)
 *
 */

/*
 * Fondy payment module
 *
 * @author     Fondy
 *
 */

class Fondy_Fondy_Block_Response extends Mage_Core_Block_Abstract
{

    protected function _toHtml()
    {
        include_once "Fondy.cls.php";
        $fodny = Mage::getModel('Fondy/Fondy');

        $settings = array(
            'merchant_id' => $fodny->getConfigData('merchant'),
            'secret_key' => $fodny->getConfigData('secret_key')
        );

        try {
            $validated = FondyForm::isPaymentValid($settings, $_POST);
            if ($validated === true) {
                list($orderId,) = explode(FondyForm::ORDER_SEPARATOR, $_POST['order_id']);

                // Payment was successful, so update the order's state, send order email and move to the success page
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);
				if ($fodny->getConfigData('after_pay_status') == Mage_Sales_Model_Order::STATE_PROCESSING){
					$order->setState($fodny->getConfigData('after_pay_status'), true, 'Gateway has authorized the payment.');
				}elseif($fodny->getConfigData('after_pay_status') == Mage_Sales_Model_Order::STATE_HOLDED){
					$order->setState($fodny->getConfigData('after_pay_status'), true, 'Gateway has authorized the payment.');
				}
				else{
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');
				}
                $order->sendNewOrderEmail();
                $order->setEmailSent(true);

                $order->save();

                Mage::getSingleton('checkout/session')->unsQuoteId();

                $url = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            } else {
                // case all is valid but order is not approved
                $url = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            }
        } catch (Exception $e) {
            // There is a problem in the response we got
            if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
                if ($order->getId()) {
                    // Flag the order as 'cancelled' and save it
                    $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, $e->getMessage())->save();
                }
            }
            $url = Mage::getUrl('checkout/onepage/failure', array('_secure' => true));
            Mage::app()->getFrontController()->getResponse()->setRedirect($url);
        }
    }
}