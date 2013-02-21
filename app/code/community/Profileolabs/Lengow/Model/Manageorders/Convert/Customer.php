<?php
/**
 * @category Lengow
 * @package  Profileolabs_Lengow_Model_Manageorders
 * @author kassim belghait
 *
 */
class Profileolabs_Lengow_Model_Manageorders_Convert_Customer extends Varien_Object
{

	
	/**
     * Convert xml node to customer model
     *
     * @param   array $data
     * @return  Mage_Customer_Model_Customer
     */
    public function toCustomer(array $data, $customer=null)
    {
    	/* @var $customer Mage_Customer_Model_Customer */
        if (!($customer instanceof Mage_Customer_Model_Customer)) {
            $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getDefaultStoreView()->getWebsiteId())
            												->loadByEmail($data['Email']);
            $customer->setImportMode(true);
            
            if(!$customer->getId())
            {
            	$customer->setWebsiteId(Mage::app()->getDefaultStoreView()->getWebsiteId());
            	$customer->setConfirmation(null);
            	$customer->setForceConfirmed(true);
            	$customer->setPasswordHash($customer->hashPassword($customer->generatePassword(8)));
            	$customer->setFromLengow(1);
            }
        }
        
         Mage::helper('core')->copyFieldset('lengow_convert_customer', 'to_customer', $data, $customer);
        if($customer->getFirstname() == "")
        	$customer->setFirstname('__');
        	
        return $customer;
    }
    
	/**
     * Convert xml node to customer address model
     *
     * @param   array $data
     * @return  Mage_Customer_Model_Address
     */
    public function addresstoCustomer(array $data, $customer = null,$type='billing')
    {
    	/* @var $customer Mage_Customer_Model_Customer */
        if (!($customer instanceof Mage_Customer_Model_Customer)) {

        	$customer = $this->toCustomer($data);
        }
        
         /* @var $address Mage_Customer_Model_Address */
         $address = Mage::getModel('customer/address');
         $address->setId(null);
         $address->setIsDefaultBilling(true);
         $address->setIsDefaultShipping(false);
         if($type == "shipping")
         {
         	$address->setIsDefaultBilling(false);
         	$address->setIsDefaultShipping(true);
         }
        
        Mage::helper('core')->copyFieldset('lengow_convert_customer', 'to_customer_address', $data, $address);
		
        if($address->getFirstname() == "")
        	$address->setFirstname(' __ ');
        
        if(strpos(strtolower($address->getCountryId()),'france') !== false)
        	$address->setCountryId('FR');
        	
        $codeRegion = substr(str_pad($address->getPostcode(),5,"0",STR_PAD_LEFT),0,2);

        //$regionId = Mage::getModel('directory/region')->loadByCode($codeRegion,$address->getCountry())->getId();
        $regionId = Mage::getModel('directory/region')->getCollection()
        												->addRegionCodeFilter($codeRegion)
        												->addCountryFilter($address->getCountry())
        												->getFirstItem()
        												->getId();
   
        if($regionId)
        	$address->setRegionId($regionId);
        else
        	$address->setRegionId(182); //Ain pour le pays FR
        	
        
        
        return $address;
    }
}