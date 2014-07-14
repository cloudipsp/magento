<?php
/*
 *
 * @category   Community
 * @package    Oplata_Oplata
 * @copyright  http://oplata.com
 * @license    Open Software License (OSL 3.0)
 *
 */

/*
 * Oplata payment module
 *
 * @author     Oplata
 *
 */

class Oplata_Oplata_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        include_once "Oplata.cls.php";
        $oplata = Mage::getModel('Oplata/Oplata');

        $data = $oplata->getFormFields();


        $state = $oplata->getConfigData('order_status');

        $order = $oplata->getQuote();
        $order->setStatus($state);
        $order->save();

        $html ='<form name="OplataForm" id="OplataForm" method="post" action="'.OplataForm::URL.'">';

        foreach ($data['fields'] as $fieldName => $field) {
            $html .= '<input type="hidden" name="'.$fieldName.'" value="'.$field.'">';
        }

        $html .= $data['button'];

        $html .= '</form>';

        return $html;
    }
}
