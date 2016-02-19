<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Authorizenet
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * DirtectPost Payment Controller
 *
 * @category   Mage
 * @package    Mage_Authorizenet
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Fondy_FondyOnPage_PaymentController extends Mage_Core_Controller_Front_Action
{

    public function gatewayAction()
    {
    if ($this->getRequest()->get("orderId"))
    {
    $arr_querystring = array(
    'flag' => 1,
    'orderId' => $this->getRequest()->get("orderId")
    );

    Mage_Core_Controller_Varien_Action::_redirect('FondyOnPage/payment/response', array('_secure' => false, '_query'=> $arr_querystring));
    }
    }

    public function redirectAction()
    {
    $this->loadLayout();
    $block = $this->getLayout()->createBlock('Mage_Core_Block_Template','fondy',array('template' => 'fondy/checkout.phtml'));
    $this->getLayout()->getBlock('content')->append($block);
    $this->renderLayout();
    }

    public function responseAction()
    {
    if ($this->getRequest()->get("flag") == "1" && $this->getRequest()->get("orderId"))
    {
    $orderId = $this->getRequest()->get("orderId");
    $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
    $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true, 'Payment Success.');
    $order->save();

    Mage::getSingleton('checkout/session')->unsQuoteId();
    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=> false));
    }
    else
    {
    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/error', array('_secure'=> false));
    }
    }
}
