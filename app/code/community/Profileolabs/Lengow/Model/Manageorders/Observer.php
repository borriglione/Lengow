<?php
class Profileolabs_Lengow_Model_Manageorders_Observer
{
	public function setCustomerTaxClassId($observer)
    {
    	if(!$this->getConfig()->applyTax() && Mage::getSingleton('checkout/session')->getIsLengow())
    	{
        	$customerGroup = $observer->getEvent()->getObject();
			$customerGroup->setData('tax_class_id', 999);
    	}

    }
    
    public function manageOrders($observer)
    {
    	try {
    			
    		$url = str_replace("index.php/", "", Mage::getBaseUrl().'Script_Profileolabs/manageorders.php');
    		$curl = curl_init($url);
    		curl_setopt($curl, CURLOPT_POST, false);
    		curl_setopt($curl, CURLOPT_HEADER, false);
    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1800);
    		$curl_response = curl_exec($curl);
    		curl_close($curl);
    	} catch (Exception $e) {
    		Mage::throwException($e);
    	}
    	
    	return $this;
    }
    
    public function sendStatusShipped($observer)
    {
    	$shipment = $observer->getShipment();
    	$order = $shipment->getOrder();

    	if(!$order->getFromLengow())
    		return $this;
    	
    	$orderIdLengow = $order->getOrderIdLengow();
    	$marketPlace = $order->getMarketplaceLengow();
    	
    	/* @var $service Profileolabs_Lengow_Model_Manageorders_Service */
    	$service = Mage::getSingleton('profileolabs_lengow/manageorders_service');
    	
    	$result = $service->updateShippedOrder($orderIdLengow);
    	
    	if($result)
		{
			if($result->error)
			{
				Mage::throwException($result->error);
			}
						
			else 
			{
				$status = $result->status;
				 
				$this->getHelper()->log($this->getHelper()->__("Order updated to lengow.Status returned %s",$status));
			}
		}
		else
		{
			$this->getHelper()->log($this->getHelper()->__("Error in update status shipped to lengow"));
			Mage::throwException($this->getHelper()->__("Error in update status shipped to lengow"));
		}
		
		return $this;
    	
    }
    
    /**
    * Retrieve config
    * @return Profileolabs_Lengow_Model_Manageorders_Config
    */
    public function getConfig()
    {
    	return Mage::getSingleton('profileolabs_lengow/manageorders_config');
    }
    
    /**
     * Get Helper
     * @return Profileolabs_Lengow_Model_Manageorders_Helper_Data
     */
    public function getHelper()
    {
    	return Mage::helper('profileolabs_lengow');
    }
    
    
}