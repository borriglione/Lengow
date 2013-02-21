<?php
/**
 * Lengow Manage Orders Config Model
 * @category   Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 */
class Profileolabs_Lengow_Model_Manageorders_Config extends Varien_Object
{
	/**
	 * Config key "WS URI TEST"
	 */
	const WS_URI_TEST = "ws_uri_test";
	
	/**
	 * Config key "WS URI PROD"
	 */
	const WS_URI_PROD = "ws_uri_prod";
	
	
	/**
	 * Config key "API KEY"
	 */
	const API_KEY = 'api_key';
	
	/**
	 * Config key "Enable manage orders"
	 */
	const ENABLED = 'enabled';
	

	/**
	 * Config key "Limit orders to import"
	 */
	const LIMIT_ORDERS = 'limit_orders';
	
	/**
	 * Config key "Create invoice manage orders"
	 */
	const CREATE_INVOICE = 'create_invoice';

	
	/**
	 * Config key "Apply Tax manage orders"
	 */
	const APPLY_TAX = 'apply_tax';

	
	/**
	 * Config key "Is test"
	 */
	const IS_TEST = 'is_test';
	
	/**
     *  Return config var
     *
     *  @param    string $key Var path key
     *  @param    int $storeId Store View Id
     *  @return	  mixed
     */
	public function getConfigData($key, $storeId = null)
	{
		if (!$this->hasData($key)) {
            $value = Mage::getStoreConfig('lengow_mo/manageorders/' . $key, $storeId);
            $this->setData($key, $value);
        }
        return $this->getData($key);
	}
	
	public function getAny($path = 'lengow_export', $group, $key, $storeId = null)
	{
        return Mage::getStoreConfig($path.'/'.$group.'/' . $key, $storeId);
	}

	/**
     *  Return config var
     *
     *  @param    string $key Var path key
     *  @param    int $storeId Store View Id
     *  @return	  mixed
     */
	public function getConfigDataExport($key, $storeId = null)
	{
		if (!$this->hasData($key)) {
            $value = Mage::getStoreConfig('lengow_export/general/' . $key, $storeId);
            $this->setData($key, $value);
        }
        return $this->getData($key);
	}
	
	/**
     *  Return config var
     *
     *  @param    string $key Var path key
     *  @param    int $storeId Store View Id
     *  @return	  mixed
     */
	public function getConfigFlag($key, $storeId = null)
	{
		if (!$this->hasData($key)) {
            $value = Mage::getStoreConfigFlag('lengow_mo/manageorders/' . $key, $storeId);
            $this->setData($key, $value);
        }
        return $this->getData($key);
	}
	
	
	
	/**
	 * Retrieve if manage orders is active
	 * 
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->getConfigFlag(self::ENABLED);
	}
	
	/**
	 * Get Limit orders
	 * @return int 
	 */
	public function getLimitOrders()
	{
		$limit = $this->getConfigData(self::LIMIT_ORDERS);
		if($limit < 1)
			$limit = 10;
			
		return $limit;
	}
	
	
	
	/**
	 * Retrieve if we must to create invoice
	 * 
	 * @return boolean
	 */
	public function createInvoice()
	{
		return $this->getConfigFlag(self::CREATE_INVOICE);
	}
	
	/**
	 * Retrieve if we must to apply tax
	 * 
	 * @return boolean
	 */
	public function applyTax()
	{
		return $this->getConfigFlag(self::APPLY_TAX);
	}

	/**
	 * Return API KEY
	 * @return string
	 */
	public function getApiKey()
	{
		return $this->getAny('lengow_apik', 'general', 'api_key');
	}
	
	/*
	 * Define is in test mode
	 * @return boolean
	 */
	public function isTest()
	{
		return $this->getConfigFlag(self::IS_TEST);
	}
	
	/**
	 * Get WS URI
	 * @return string uri of web service
	 */
	public function getWsUri()
	{
		//Gestion des différents group Lengow
		$_groupIds = '';
		$allStores = Mage::app()->getStores();
		foreach ($allStores as $_eachStoreId => $val)
		{
			$_groupIds .= $this->getAny('lengow_apik', 'general', 'group', Mage::app()->getStore($_eachStoreId)->getId()) . ',';
		}
		$_groupIds = substr($_groupIds, 0, strlen($_groupIds) - 1);

		if($this->isTest())
			return $this->getConfigData(self::WS_URI_TEST);
		

		$url = $this->getConfigData(self::WS_URI_PROD).date("Y-m-d", strtotime("-".$this->getConfigData('period_orders')." day")).'/'.date("Y-m-d").'/'.$this->getAny('lengow_apik', 'general', 'login').'/orders/magento/'.$_groupIds.'/';
//		$url = $this->getConfigData(self::WS_URI_PROD).'2013-01-01/2013-01-01/'.$this->getConfigDataExport('login').'/orders/magento/'.$_groupIds.'/';
		return $url; 
	}
}