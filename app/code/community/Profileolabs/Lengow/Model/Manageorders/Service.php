<?php
/**
 * Lengow Service
 * @category   Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 */
class Profileolabs_Lengow_Model_Manageorders_Service extends Varien_Object
{
	/**
	 * Method's name for get orders
	 */
	const METHOD_GET_ORDERS = "getOrders";
	
	/**
	 * Method's name for send order ids
	 */
	const METHOD_SEND_ORDERS = "validOrders";
	
	/**
	 * Method's name for update status order ids
	 */
	const METHOD_UPDATE_STATUS_ORDERS = "updateStatus";
	
	/**
	 * Method's name for login
	 */
	const METHOD_LOGIN = "getLogin";
	
	/**
	 * 
	 * @var Zend_Http_Client
	 */
	protected $_client = null;
	
	/**
	 * 
	 * @var SimpleXMLElement
	 */
	protected $_xml = null;
	
	/**
	 * Config Data of Module Manageorders
	 * @var Profileolabs_Lengow_Model_Manageorders_Config
	 */
	protected $_config = null;
	
	protected function _getApiKey()
	{
		return Mage::getStoreConfig('lengow_apik/general/api_key', $storeId);
	}
	
	/**
	 * Get client HTTP
	 * @return Zend_Http_Client
	 */
	public function getClient()
	{
		if(is_null($this->_client))
		{
			$wsUri = $this->getConfig()->getWsUri();

			//adapter options
			$config = array('curloptions' => array(/*CURLOPT_FOLLOWLOCATION => true,*/
									   //CURLOPT_POST=>true,
									   CURLOPT_SSL_VERIFYHOST=>0,
									   CURLOPT_SSL_VERIFYPEER=>0,
									   CURLOPT_HEADER=>false,
									   CURLOPT_RETURNTRANSFER=>true),
			);
			try {
				
				//innitialize http lcient and adapter curl
				//$adapter = new Zend_Http_Client_Adapter_Curl();
				$adapter = new Profileolabs_Lengow_Model_Manageorders_Service_Http_Client_Adapter_Curl();
				$this->_client = new Zend_Http_Client($wsUri);
				$this->_client->setAdapter($adapter);
				$adapter->setConfig($config);
				
			} catch (Exception $e) {
				Mage::throwException($e);
			}
		}
		
		return $this->_client;
	}
	
	/**
	 * Connect to Lengow and Call method
	 * 
	 * @param string $apiKey
	 * @param string $method
	 */
	protected function _connect($apiKey,$method,array $additional=null)
	{
		if(empty($apiKey))
			Mage::throwException("API Key (Token) is empty");
			
		$data = array("call" => $method,"user_token"=>$apiKey);
		if($additional)
			$data = array_merge($data,$additional);
		
		//try {

			/* @var $response Zend_Http_Response */
			//set Post Params
			$this->getClient()->setParameterPost('data',base64_encode(serialize($data)));
			//send the request
			$response = $this->getClient()->request(Zend_Http_Client::POST);

			//die($response->getBody());
			//load response at xml (SimpleXMLElement)
    		$this->_xml = simplexml_load_string($response->getBody(), 'Varien_Simplexml_Element', LIBXML_NOCDATA);

    	//Mage::log($this->_xml,null,"flux_order.log");

    		//Mage::throwException(Mage::helper('profileolabs_lengow')->__('TEST KASSIM'));	
    		
    		
    		if(!($this->_xml instanceof Varien_Simplexml_Element))
				Mage::throwException(Mage::helper('profileolabs_lengow')->__("Result is not Varien_Simplexml_Element"));
			elseif($this->_xml->error)
				Mage::throwException(Mage::helper('profileolabs_lengow')->__('API key (Token) not valid'));
    		
	/*	} catch (Exception $e) {
			Mage::throwException($e);
		}*/
	    
		return $this->_xml;
	}
	
	/**
	 * Retrieve orders
	 * 
	 */
	public function getOrders()
	{
		return $this->_connect($this->_getApiKey(),self::METHOD_GET_ORDERS);
	}
	
	/**
	 * Send orders ids imported
	 * @param array $orderIds 
	 */
	public function sendValidOrders(array $orderIds)
	{
		return $this->_connect($this->_getApiKey(),self::METHOD_SEND_ORDERS,array('orders'=>$orderIds));
	}
	
	/**
	 * Update orders id shipped
	 * @param string $orderId
	 */
	public function updateShippedOrder($orderId)
	{
		return $this->updateStatus($orderId, "Sent");
	}
	
	/**
	 * Update status order 
	 * @param string $orderId
	 * @param string $status
	 */
	protected function updateStatus($orderId,$status)
	{
		return $this->_connect($this->_getApiKey(),self::METHOD_UPDATE_STATUS_ORDERS,array('order'=>$orderId,'status'=>$status));
	}
	
	/**
	 * Retrieve login
	 * @param string|null $apiKey
	 */
	public function getLogin($apiKey=null)
	{
		if(is_null($apiKey))
			$apiKey = $this->_getApiKey();
			
		return $this->_connect($apiKey,self::METHOD_LOGIN);
	}
	
	/**
	 * Check if ApiKey is valid
	 * @param string $apiKey
	 */
	public function checkApiKey($apiKey)
	{
		if($this->getLogin($apiKey)->error)
			return false;
			
		return true;
	}
	
	/**
	 * Retrieve config
	 * @return Profileolabs_Lengow_Model_Manageorders_Config
	 */
	public function getConfig()
	{
		if(is_null($this->_config))
		{
			$this->_config = Mage::getSingleton('profileolabs_lengow/manageorders_config');
		}
		
		return $this->_config;
	}
}