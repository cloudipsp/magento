<?php

class Fondy_Fondy_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('fondy/info.phtml'); 

    }
	protected function _toHtml()
    {
        return parent::_toHtml();
    }
}