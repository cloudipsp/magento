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
        $fondy = Mage::getModel('Fondy/Fondy');
        $data = $this->getRequest()->getParams();
        try {
            list($orderId,) = explode(FondyForm::ORDER_SEPARATOR, $data['order_id']);
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($orderId);

            //Try to get merchant by zip
            $zipCode = $order->getShippingAddress()->getPostcode();
            $settings = $fondy->getMerchantByZip($zipCode, unserialize($fondy->getConfigData('merchants')));
           
            $validated = FondyForm::isPaymentValid($settings, $data);
            if ($validated === true) {
                // Payment was successful, so update the order's state, send order email and move to the success page
                if ($fondy->getConfigData('after_pay_status') == Mage_Sales_Model_Order::STATE_PROCESSING) {
                    $order->setState($fondy->getConfigData('after_pay_status'), true, sprintf('Gateway has authorized the payment. Payment ID: %s. Merchant ID: %s', $data['order_id'], $settings['merchant_id']));
                } elseif ($fondy->getConfigData('after_pay_status') == Mage_Sales_Model_Order::STATE_HOLDED) {
                    $order->setState($fondy->getConfigData('after_pay_status'), true, sprintf('Gateway has authorized the payment. Payment ID: %s. Merchant ID: %s', $data['order_id'], $settings['merchant_id']));
                } else {
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, sprintf('Gateway has authorized the payment. Payment ID: %s. Merchant ID: %s', $data['order_id'], $settings['merchant_id']));
                }

                //invoice and payment
                $invoice = Mage::getModel('sales/Service_Order', $order)->prepareInvoice();
                $invoice->register()->pay();
                $invoice->getOrder()->setIsInProcess(true);
                $transactionSave = Mage::getModel('core/resource_transaction')->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                $payment = $order->getPayment();
                $payment->setTransactionId('fondy' . $payment['order_id'])
                    ->setCurrencyCode($order->getBaseCurrencyCode())
                    ->setPreparedMessage(sprintf('Gateway has authorized the payment. Payment ID: %s. Merchant ID: %s', $data['order_id'], $settings['merchant_id']))
                    ->setIsTransactionClosed(1);
                //save updated order
                $order->sendNewOrderEmail();
                $order->setEmailSent(true);
                $order->save();
                Mage::getSingleton('checkout/session')->unsQuoteId();

                //redirect to success
                $url = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            } else {
                // case all is valid but order is not approved
                // success because order may be in processing state.
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