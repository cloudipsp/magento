<?php
class Fondy_Fondy_Block_Config_Admin extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function _prepareToRender()
    {
        $this->addColumn('city_index', array(
            'label' => __('Index'),
            'style' => 'width:300px',
        ));
        $this->addColumn('merchant_id', array(
            'label' => __('Merchant ID'),
            'style' => 'width:100px',
        ));
		$this->addColumn('secret_key', array(
            'label' => __('Secret Key'),
            'style' => 'width:100px',
        ));	
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}