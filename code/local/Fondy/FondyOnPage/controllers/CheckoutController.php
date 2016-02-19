<?php
class Fondy_FondyOnPage_CheckoutController extends Mage_Core_Controller_Front_Action {

    protected function _expireAjax() {
        if (!Mage::getSingleton('FondyOnPage/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    public function indexAction() {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template','fondy',array('template' => 'fondy/checkout.phtml'));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

}

?>
