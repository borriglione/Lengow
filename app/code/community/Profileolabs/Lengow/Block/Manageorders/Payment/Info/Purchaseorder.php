<?php
/**
 * @category    Lengow
 * @package     Profileolabs_Lengow
 * @author kassim belghait
 */


class Profileolabs_Lengow_Block_Manageorders_Payment_Info_Purchaseorder extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('profileolabs/lengow/manageorders/payment/info/purchaseorder.phtml');
    }

}
