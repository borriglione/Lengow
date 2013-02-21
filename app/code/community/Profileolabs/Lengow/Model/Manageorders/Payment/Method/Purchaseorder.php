<?php
/**
 * @category    Lengow
 *  @package    Profileolabs_Lengow
 * @author kassim belghait
 */

class Profileolabs_Lengow_Model_Manageorders_Payment_Method_Purchaseorder extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'lengow_purchaseorder';
    //protected $_formBlockType = 'payment/form_purchaseorder';
    protected $_infoBlockType = 'profileolabs_lengow/manageorders_payment_info_purchaseorder';

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Profileolabs_Lengow_Model_Manageorders_Payment_Method_Purchaseorder
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $this->getInfoInstance()->setAdditionalData($data->getMarketplace());
        return $this;
    }
    
	/**
     * Check whether payment method can be used
     * TODO: payment method instance is not supposed to know about quote
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if(Mage::getSingleton('checkout/session')->getIsLengow())
        	return true;
        	
        return parent::isAvailable($quote);
    }
}
