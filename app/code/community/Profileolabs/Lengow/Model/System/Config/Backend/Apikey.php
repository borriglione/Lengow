<?php
/**
 * Lengow Backend Model for Config Api key
 * @category   Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 */
class Profileolabs_Lengow_Model_System_Config_Backend_Apikey extends Mage_Core_Model_Config_Data
{

	protected function _beforeSave()
	{
		parent::_beforeSave();
		
		if((boolean)$this->getFieldsetDataValue('enabled') && $this->getValue() == "")
			Mage::throwException(Mage::helper('profileolabs_lengow')->__('API Key (Token) is empty'));
		
		if($this->isValueChanged())
		{
			/* @var $service Profileolabs_Lengow_Model_ManageOrders_Service */
			$service = Mage::getSingleton('profileolabs_lengow/manageorders_service');
			
			if((boolean)$this->getFieldsetDataValue('enabled') && !$service->checkApiKey($this->getValue()))
				Mage::throwException(Mage::helper('profileolabs_lengow')->__('API key (Token) not valid'));
			
		}
	}
	
}