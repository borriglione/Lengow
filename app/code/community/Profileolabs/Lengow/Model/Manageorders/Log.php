<?php
/**
 * Error Log
 * @category Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 *
 */
class Profileolabs_Lengow_Model_Manageorders_Log extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		$this->_init('profileolabs_lengow/manageorders_log');
	}
	
	/**
	 * Save message event
	 * @param $message string
	 * @param $orderId int
	 */
	public function log($message,$orderId = null)
	{
		$orderMessage ="";
		if(!is_null($orderId))
		{
			$orderMessage = Mage::helper('profileolabs_lengow')->__('OrderID')." Lengow #".$orderId." ";
		}	

		$message = $orderMessage.$message;
		
		$this->setMessage($message);
		
		return $this->save();
	}
	
}