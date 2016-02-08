<?php
/*
 *
 * @category   Community
 * @package    MagentoCenter_Wm
 * @copyright  http://Magentocenter.org
 * @license    Open Software License (OSL 3.0)
 *
 */

/*
 * Webmoney Transfer payment module
 *
 * @author     Magentocenter.org    -   Magento Store Setup, data migration, upgrades and much more!
 *
 */

class Fondy_Fondy_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Fondy/form.phtml');

    }
}