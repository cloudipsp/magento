<?php

class Fondy_Fondy_Block_Info extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('fondy/info_form.phtml'); 

    }
	protected function _toHtml()
    {
        return parent::_toHtml();
    }
}