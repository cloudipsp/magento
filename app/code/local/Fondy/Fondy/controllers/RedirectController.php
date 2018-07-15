<?php
class Fondy_Fondy_RedirectController extends Mage_Core_Controller_Front_Action {

    protected function _expireAjax() {
        if (!Mage::getSingleton('Fondy/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    public function indexAction() {
        $this->getResponse()
                ->setHeader('Content-type', 'text/html; charset=utf8')
                ->setBody($this->getLayout()
                ->createBlock('Fondy/redirect')
                ->toHtml());
    }

}

?>
