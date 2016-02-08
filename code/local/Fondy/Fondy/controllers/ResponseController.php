<?php
class Fondy_Fondy_ResponseController extends Mage_Core_Controller_Front_Action {
    
	public function indexAction() {

        $this->getResponse()
            ->setHeader('Content-type', 'text/html; charset=utf8')
            ->setBody($this->getLayout()
            ->createBlock('Fondy/response')
            ->toHtml());
    }
}

?>
