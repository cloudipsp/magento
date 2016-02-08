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

class Fondy_Fondy_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        include_once "Fondy.cls.php";
        $oplata = Mage::getModel('Fondy/Fondy');

        $data = $oplata->getFormFields();


        $state = $oplata->getConfigData('order_status');

        $order = $oplata->getQuote();
        $order->setStatus($state);
        $order->save();
		//print_r($data['fields'])die;
        $html ='<form name="FondyForm" id="FondyForm" method="post" action="'.FondyForm::URL.'">';

        foreach ($data['fields'] as $fieldName => $field) {
            $html .= '<input type="hidden" name="'.$fieldName.'" value="'.$field.'">';
        }

        $html .= $data['button'];

        $html .= '</form>';

        return $html;
    }
}
