<?php

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
        $post = Mage::app()->getRequest()->getParams();
		if (empty($post)) {
                $callback = json_decode(file_get_contents("php://input"));
                $post = array();
                foreach ($callback as $key => $val) {
                    $_POST[$key] = $val;
                }
        }

        try {
            $validated = FondyForm::isPaymentValid($settings, $post);
	
            if ($validated === true) {

				$oid = json_decode(base64_decode( $post['data']),TRUE)['order']['order_id'];
				$payment = json_decode(base64_decode( $post['data']),TRUE)['order'];
                list($orderId,) = explode(FondyForm::ORDER_SEPARATOR, $oid);

                // Payment was successful, so update the order's state, send order email and move to the success page
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);

                if ($fodny->getConfigData('after_pay_status') == Mage_Sales_Model_Order::STATE_PROCESSING){
                    $order->setState($fodny->getConfigData('after_pay_status'), true, 'Gateway has authorized the payment.' .  ' order ID = ' . $payment['order_id']);
                }elseif($fodny->getConfigData('after_pay_status') == Mage_Sales_Model_Order::STATE_HOLDED){
                    $order->setState($fodny->getConfigData('after_pay_status'), true, 'Gateway has authorized the payment.' . ' order ID = ' . $payment['order_id']);
                }
                else{
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.' . ' order ID = ' . $payment['order_id']);
                }

                //update merchant
                $items = $order->getAllItems();
                foreach ($items as $i) {
                    $quantity_second_merchant = Mage::getResourceModel('catalog/product')->getAttributeRawValue($i->getProductId(), 'merchant_qty');				
					if ($i->product_type != 'configurable') {										
						if(empty($quantity_second_merchant) or $quantity_second_merchant == ''){
							$attr = Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('frontend_label', 'ВНаличииMerchant');
							$attribute_code = $attr->getData('attribute_code')[0]['attribute_code'];
							$quantity_second_merchant = Mage::getResourceModel('catalog/product')->getAttributeRawValue($i->getProductId(), $attribute_code);
						}

						// second merchant id
						if (isset($quantity_second_merchant) and $quantity_second_merchant > 0) {

							$new_quantity_second_merchant = $quantity_second_merchant - $i->getQtyOrdered(); //calculate new quantity
							if ($new_quantity_second_merchant < 0)
								$new_quantity_second_merchant = 0; //set zero if $new_quantity_second_merchant < 0
							Mage::getSingleton('catalog/product_action')
								->updateAttributes(array(
									$i->getProductId()),
									array('merchant_qty' => $new_quantity_second_merchant),
									0); //setting new attr value
							//Mage::throwException();

						}
					}
                }			
                $invoice = Mage::getModel('sales/Service_Order', $order)->prepareInvoice();
                $invoice->register()->pay();
				$invoice->getOrder()->setIsInProcess(true);
				$invoice->sendEmail(true, '');
                $transactionSave = Mage::getModel('core/resource_transaction')->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
                $payment = $order->getPayment();
                $payment->setTransactionId('fondy'. $payment['order_id'])
                    ->setCurrencyCode($order->getBaseCurrencyCode())
                    ->setPreparedMessage('Gateway has authorized the payment.')
                    ->setIsTransactionClosed(0);
					
                $order->sendNewOrderEmail();				
                $order->setEmailSent(true);
                $order->save();

                Mage::getSingleton('checkout/session')->unsQuoteId();
				Mage::getSingleton('checkout/cart')->truncate()->save();
                $url = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            } else {
                // case all is valid but order is not approved
                $url = Mage::getUrl('checkout/onepage/failure', array('_secure' => true));
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