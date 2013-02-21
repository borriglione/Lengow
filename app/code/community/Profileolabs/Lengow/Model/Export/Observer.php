<?php
class Profileolabs_Lengow_Model_Export_Observer
{
	/**
	 * @deprecated deprecated since 0.1.1
	 * @param Varien_Object $observer
	 */
	public function generateFlow($observer)
	{
		try {
			$url = str_replace("index.php/", "", Mage::getBaseUrl().'Script_Profileolabs/generate_flow.php');
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
}