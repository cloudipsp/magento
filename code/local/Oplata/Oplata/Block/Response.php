<?php
/*
 *
 * @category   Community
 * @package    Oplata_Oplata
 * @copyright  http://Oplata.com
 * @license    Open Software License (OSL 3.0)
 *
 */

/*
 * Oplata payment module
 *
 * @author     Oplata
 *
 */

class Oplata_Oplata_Block_Response extends Mage_Core_Block_Abstract
{

    protected function _toHtml()
    {

//        echo "<pre>";
//        echo "Response\n";
//        print_r($_POST);
//        return var_export($_POST, true);

        include_once "Oplata.cls.php";
        $oplata = Mage::getModel('Oplata/Oplata');

        $settings = array(
            'merchant_id' => $oplata->getConfigData('merchant'),
            'secret_key' => $oplata->getConfigData('secret_key')
        );

        try {
            $validated = OplataForm::isPaymentValid($settings, $_POST);
            if ($validated === true) {
                list($orderId,) = explode(OplataForm::ORDER_SEPARATOR, $_POST['order_id']);

                // Payment was successful, so update the order's state, send order email and move to the success page
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');

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