<?php
/**
 * Orders getted here
 * 
 * @category Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 *
 */
class Profileolabs_Lengow_Model_Manageorders_Order extends Varien_Object
{
	/**
	 * @var Mage_Sales_Model_Quote
	 */
	protected $_quote = null;
	
	/**
	 * @var Mage_Customer_Model_Customer
	 */
	protected $_customer = null;
	
	/**
	* Config Data of Module Manageorders
	* @var Profileolabs_Lengow_Model_Manageorders_Config
	*/
	protected $_config = null;
	
	protected $_paymentMethod = 'lengow_purchaseorder';
	
	protected $_shippingMethod = 'lengow_lengow';
	
	protected $_nb_orders_imported = 0;
	
	protected $_nb_orders_read = 0;
	
	protected $_ordersIdsImported = array();
	
	protected $_orderIdsAlreadyImported = array();
	
	protected $_result;
	
	protected $_resultSendOrder = "";
	
	protected $_isUnderVersion14 = null;
	
	protected $_nb_orders_updated = 0;
	 /**
     * Product model
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_productModel;

	protected $store_id = 0;
    

    public function getResultSendOrder()
    {
    	return $this->_resultSendOrder;
    }
    
    public function isUnderVersion14()
    {
    	if(is_null($this->_isUnderVersion14))
    	{
    		$this->_isUnderVersion14 = $this->getHelper()->isUnderVersion14();
    	}
    	return $this->_isUnderVersion14;
    }
    
 	/**
     * Retrieve product model cache
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProductModel()
    {
        if (is_null($this->_productModel)) {
            $productModel = Mage::getModel('catalog/product');
            $this->_productModel = Mage::objects()->save($productModel);
        }
        return Mage::objects()->load($this->_productModel);
    }
    
    public function getOrderIdsAlreadyImported()
    {
    	if(count($this->_orderIdsAlreadyImported) < 1)
    	{
    		$orders = Mage::getModel('sales/order')->getCollection()
    												->addAttributeToFilter('from_lengow',1)
    												->addAttributeToSelect('order_id_lengow');


    		foreach($orders as $order)
    		{
    			$this->_orderIdsAlreadyImported[] = $order->getOrderIdLengow();
    		}
    	}
    	
    	return $this->_orderIdsAlreadyImported;
    }
    
    public function isAlreadyImported($idLengow)
    {
    	$alreadyImported = $this->getOrderIdsAlreadyImported();
    	if(in_array($idLengow,$alreadyImported))
    		return true;
    	
    	return false;
    }
    
    public function getSession()
    {
    	return Mage::getSingleton('checkout/session');
    }
    
    protected function _getQuote()
    {
    	if(intval($this->store_id)>0) {
    		$storeId = $this->store_id;
    	}
    	else {
	    	$storeId = Mage::app()->getDefaultStoreView()->getId();
    	}
    	$this->getSession()->getQuote()->setStoreId($storeId);

    	return $this->getSession()->getQuote();
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

	/**
	 * Get orders and create it
	 */
	public function manageOrders()
	{
		if(!$this->getConfig()->isEnabled())
			return;
		
		require_once('Connector.php');
		
		if(Mage::app()->getStore()->getCode() != 'admin')
			Mage::app()->setCurrentStore('admin');
		
			
		$service = Mage::getSingleton('profileolabs_lengow/manageorders_service');
		
			try {
			
				/* @var $this->_result Varien_Simplexml_Element */
				$this->_result = $service->getOrders(true);
				$this->_nb_orders_imported = 0;
			
		
			} catch (Exception $e) {
				Mage::logException($e);
				$message = Mage::helper('profileolabs_lengow')->__('Orders can not getted.');
				$this->getHelper()->log($message);

				Mage::throwException($e);
			}
			
			//We parse result
			$nodes = $this->_result->children();
			$message_order_already = '[ '.Mage::helper('profileolabs_lengow')->__('Order already imported').' : ';
			$message_order_imported = ' / [ '.Mage::helper('profileolabs_lengow')->__('Order imported').' : ';
			$nbOrderToUpdate = 0;
			
			foreach($nodes as $childName => $child)
			{	
				$orderLw = $this->getHelper()->asArray($child);

				if($this->isAlreadyImported($orderLw['IdOrder'])) {
					$orderMg = Mage::getModel('sales/order')->loadByAttribute('order_id_lengow', $orderLw['IdOrder'])->getData();
					$message_order_already .= $orderLw['IdOrder'].", ";
					//test si la commande à changé de statut
					if($orderLw['State'] != $orderMg['state'])
					{
						$this->_updateOrderStatus($orderLw['IdOrder'], $orderLw['State']);
						$nbOrderToUpdate++;
					}
					continue;
				}
				else {
					$message_order_imported .= $orderLw['IdOrder'].", ";
					//si shipped créer un shipment
				}

				
				$this->_nb_orders_read++;
				
				$this->createAllForOrder($orderLw);

				if($this->_nb_orders_imported == $this->getConfig()->getLimitOrders()) {
					$message = "Limit reached : ".$this->getConfig()->getLimitOrders();
					$this->getHelper()->log($message);
					break;
				}
				
			
			}


			$message_order = substr($message_order_already, 0, -2).' ] '.substr($message_order_imported, 0, -2).' ]';
			$this->getHelper()->log($message_order);

			$ConfigLengow = new Profileolabs_Lengow_Model_Manageorders_Config();
			$Lengow = new LengowConnector();
			$Lengow->lengow_token = $ConfigLengow->getApiKey();
			$Lengow->idClient = $ConfigLengow->getConfigDataExport('login');

			// Enregistrement LOG chez Lengow
			$array = array(
				'idClient' => (int)$ConfigLengow->getConfigDataExport('login'), 
				'idGroup' => (int)$ConfigLengow->getConfigDataExport('group'),
				'cde_total' => (int)$this->_nb_orders_imported,
				'cde_success' => (int)$this->_nb_orders_imported,
				'cde_error' => (int)0,
				'logs' => $message_order,
				'logs_client' => $message_order
			);
			$Lengow->callMethod('insertLogMagento', $array);
			unset($array, $ConfigLengow, $Lengow);

			try {	
				if($this->_nb_orders_imported > 0)
				{
					$result = $service->sendValidOrders($this->_ordersIdsImported);
					
					if($result)
					{
						if($result->error)
						{
							Mage::throwException($result->error);
						}
						
						$this->_resultSendOrder = $result->status;
					}
					else
					{
						$this->getHelper()->log("Error in order ids validated");
						Mage::throwException("Error in order ids validated");
					}
				}
			
			
			} catch (Exception $e) {
				$this->getHelper()->log($e->getMessage());
				Mage::throwException($e);
			}
		return $this;
	}
    
	/**
	 * Inititalize the quote with minimum requirement
	 * @param array $orderLw
	 */
    protected function _initQuote(array $orderLw)
    {
                
            //Super mode is setted to bypass check item qty ;)
			$this->_getQuote()->setIsSuperMode(true);
			
			//Set boolean lengow and shipping prices in session for shopping method
			$this->getSession()->setIsLengow(true);
			
			$this->_getQuote()->setCustomer($this->_customer);
			return 0;	 
	}
    
	
	/**
	 * Create or Update customer with converter
	 * @param array $data Data From Lengow
	 */
	protected function _createCustomer(array $data)
	{
		try {
			
			foreach($data['BillingAddress'] as $k => $a)
			{
				if($a['Email'] == '')
					$data['BillingAddress'][$k]['Email'] = 'no-mail-'.$data['IdOrder'].'@'.strtolower($data['Marketplace']).'.com';
				if($a['Phone'] == '')
					$data['BillingAddress'][$k]['Phone'] = '0251000000';
			}
			foreach($data['ShippingAddress'] as $k => $a)
			{
				if($a['Email'] == '')
					$data['ShippingAddress'][$k]['Email'] = 'no-mail-'.$data['IdOrder'].'@'.strtolower($data['Marketplace']).'.com';
				if($a['Phone'] == '')
					$data['ShippingAddress'][$k]['Phone'] = '0251000000';
			}

			$convert_customer = Mage::getModel('profileolabs_lengow/manageorders_convert_customer');
			$this->_customer = $convert_customer->toCustomer(current($data['BillingAddress']));
			$billingAddress = $convert_customer->addresstoCustomer(current($data['BillingAddress']),$this->_customer);
			
			$this->_customer->addAddress($billingAddress);
			
			$shippingAddress = $convert_customer->addresstoCustomer(current($data['ShippingAddress']),$this->_customer,'shipping');
			$this->_customer->addAddress($shippingAddress);
			

			$this->_customer->save();
		
		} catch (Exception $e) {
			Mage::throwException($e);
		}
		
	}
	
	public function createAllForOrder($orderLw)
	{
		try {
			
			//$this->_quote = null;
			$this->_customer = null;

			
			//Create or Update customer with addresses
			$this->_createCustomer($orderLw);
			$quoteId = $this->_initQuote($orderLw);

			//Add products to quote with data from Lengow
			$this->_addProductsToQuote($orderLw);
			
			$order = null;
			if(!$this->isUnderVersion14())
				$order = $this->_saveOrder($orderLw, $quoteId);
			else
				$order = $this->_saveOrder13($orderLw, $quoteId);
			

			$this->_nb_orders_imported++;
			
			if(!is_null($order) && $order->getId())
				$this->_changeDateCreatedAt($order, $orderLw['OrderDate']);
			
			//Erase session for the next order
			$this->getSession()->clear();
				
			
			
		}
		catch(Exception $e)
		{
			$this->getHelper()->log($e->getMessage(),$orderLw['IdOrder']);
			//Erase session for the next order
			$this->getSession()->clear();
			
		}
	}
	
	protected function _changeDateCreatedAt($order,$date)
	{
		try {
			
			$order->setCreatedAt($date);
			$order->save();
		} catch (Exception $e) {
			Mage::logException($e);
			Mage::throwException($message);
		}
	}
	
	
	/**
	 * Add products to quote with data from ShoppinfFlux
	 * @param array $orderLw
	 */
	protected function _addProductsToQuote(array $orderLw)
	{
		$totalAmount = $orderLw['TotalAmount'];
		$productsLw = current($orderLw['Products']);
		$productsToIterate = current($productsLw);

		foreach($productsToIterate as $key=>$productLw)
		{
			$sku = $productLw['SKU'];
			if(($productId = $this->getProductModel()->getResource()->getIdBySku($sku)) != false)
			{
				$product =Mage::getModel('catalog/product')->load($productId);// $this->getProductModel()->reset()->load($productId);
				
				$request = new Varien_Object(array('qty'=>$productLw['Quantity']));
				if($product->getTypeId() == 'simple' && $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
				{
					
					$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
	            						->getParentIdsByChild($product->getId());
					
					if(count($parentIds))
					{
						$parentId = current($parentIds);
						
						$attributesConf = $this->getHelper()->getAttributesConfigurable($parentId);
						$superAttributes = array();
						
						foreach ($attributesConf as $attribute) {	
	
							$attributeCode = $attribute['attribute_code'];
							$attributeId = $attribute['attribute_id'];
							
							$superAttributes[$attributeId] = $product->getData($attributeCode);
						}
						
						$product = Mage::getModel('catalog/product')->load($parentId);
						
						$request->setData('super_attribute',$superAttributes);
						
					}
				}
				
				
				$item = $this->_getQuote()->addProduct($product,$request);
				
				if(!is_object($item))
				{
					$this->getSession()->clear();
					Mage::throwException("le produit sku = ".$sku." n'a pas pu être ajouté! Id = ".$product->getId()." Item = ".(string)$item);
				}
				
				
				//Save the quote with the new product
				$this->_getQuote()->save();
				
				if($this->isUnderVersion14())
					$productLw['Price'] = $productLw['Price'] / $productLw['Quantity'];
				
				//Modify Item price
				$item->setCustomPrice($productLw['Price']);
				$item->setOriginalCustomPrice($productLw['Price']);
				$item->save();	
				
				if(is_object($parentItem = $item->getParentItem()))
				{
					$parentItem->setCustomPrice($productLw['Price']);
					$parentItem->setOriginalCustomPrice($productLw['Price']);
					$parentItem->save();
				}

				
			}
			else
			{
				
				$this->getSession()->clear();
				Mage::throwException("le produit sku = ".$sku." n'existe plus en base!");
				
			}
		}
			if(!$this->_customer->getDefaultBilling() || !$this->_customer->getDefaultShipping())
				$this->_customer->load($this->_customer->getId());
			
			$customerAddressBillingId = $this->_customer->getDefaultBilling();
			$customerAddressShippingId = $this->_customer->getDefaultShipping();
			
			//Set billing Address
			$addressBilling =  $this->_getQuote()->getBillingAddress();
			//Make sure addresses will be saved without validation errors
			$addressBilling->setShouldIgnoreValidation(true);
			$customerAddressBilling = Mage::getModel('customer/address')->load($customerAddressBillingId);
			$addressBilling->importCustomerAddress($customerAddressBilling)->setSaveInAddressBook(0);

			//Set shipping Address
        	$addressShipping = $this->_getQuote()->getShippingAddress();
        	//Make sure addresses will be saved without validation errors
        	$addressShipping->setShouldIgnoreValidation(true);
        	$customerAddressShipping = Mage::getModel('customer/address')->load($customerAddressShippingId);
        	$addressShipping->importCustomerAddress($customerAddressShipping)->setSaveInAddressBook(0);
        	$addressShipping->setSameAsBilling(0);
        	
        	
        	//Convert shipping price by tax rate
        	$shippingPrice = (float)$orderLw['TotalShipping'];
        	$this->getSession()->setShippingPrice($shippingPrice);
        	if (!Mage::helper('tax')->shippingPriceIncludesTax() && Mage::helper('tax')->getShippingTaxClass(null)) {
        		$percent = null;
        		$pseudoProduct = new Varien_Object();
        		$pseudoProduct->setTaxClassId(Mage::helper('tax')->getShippingTaxClass(null));
        	
        		$taxClassId = $pseudoProduct->getTaxClassId();
        		if (is_null($percent)) {
        			if ($taxClassId) {
        				$request = Mage::getSingleton('tax/calculation')->getRateRequest($addressShipping, $addressBilling, null, null);
        				$percent = Mage::getSingleton('tax/calculation')->getRate($request->setProductClassId($taxClassId));
        	
        				if($percent !== false || !is_null($percent))
        				{
        					 
        					$shippingPrice = $shippingPrice - ($shippingPrice/(100+$percent)*$percent);
        					$this->getSession()->setShippingPrice($shippingPrice);
        	
        				}
        			}
        		}
        	}
        	
        	//Set shipping Mehtod and collect shipping rates
        	$addressShipping->setShippingMethod($this->_shippingMethod)->setCollectShippingRates(true);
        	
        	$this->_getQuote()->collectTotals();
			$this->_getQuote()->save();
	        
        	//Set payment method
        	/* @var $payment Mage_Sales_Quote_Payment */
        	$this->_getQuote()->getShippingAddress()->setPaymentMethod($this->_paymentMethod);
        	$payment = $this->_getQuote()->getPayment();
        	$dataPayment = array('method'=>$this->_paymentMethod, 'marketplace'=>$orderLw['Marketplace']);
        	$payment->importData($dataPayment);
        	
        	$this->_getQuote()->collectTotals();
			$this->_getQuote()->save();
        	        	
		
	}
	
	protected function _updateOrderStatus($order_id_lengow, $status)
	{
		$this->_nb_orders_updated++;
		$order=Mage::getModel('sales/order')->loadByAttribute('order_id_lengow', $order_id_lengow);
		$order_data = $order->getData();
		$State = $order_data['state'];
		//Si la commande est shipped ou processing création d'un facture
		//Si shipped, création de l'envoi
		if($State == 'shipped')
		{
			try {
				if($order->canInvoice()) {
					//Create invoice with pending status
					$invoiceId = Mage::getModel('sales/order_invoice_api')
						->create($order->getIncrementId(), array());

					$invoice = Mage::getModel('sales/order_invoice')
									->loadByIncrementId($invoiceId);

					//set invoice status "paid"
					$invoice->capture()->save();
				}
			}catch (Mage_Core_Exception $e) {
			// print_r($e);
			}
try {
    if($order->canShip()) {
        //Create shipment
        $shipmentid = Mage::getModel('sales/order_shipment_api')
                        ->create($order->getIncrementId(), array());
        //Add tracking information
        $ship = Mage::getModel('sales/order_shipment_api')
                        ->addTrack($order->getIncrementId(), array());       
    }
}catch (Mage_Core_Exception $e) {
//
}
		}
		//Passe les états
		//Tous n'existent pas sur les marketplace
		elseif($State == 'processing')
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
		elseif($State == 'new')
			$order->setState(Mage_Sales_Model_Order::STATE_NEW, true);
		elseif($State == 'pending')
			$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
		elseif($State == 'closed')
			$order->setState(Mage_Sales_Model_Order::STATE_CLOSED, true);
		elseif($State == 'canceled')
			$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
		elseif($State != 'shipped' && $State != 'complete')
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
	}

	/**
	 * Save the new order with the quote
	 * @param array $orderLw
	 */
	protected function _saveOrder(array $orderLw, $quoteId)
	{
		$orderIdLengow = (string)$orderLw['IdOrder'];
		$orderCurrency = (string)$orderLw['Currency'];
		$orderStoreID = (string)$orderLw['Store_Id'];

		$additionalData = array("from_lengow"=>1,
								"marketplace_lengow"=>$orderLw['Marketplace'],
								"fees_lengow"=>(float)$orderLw['Fees'],
								"global_currency_code"=>$orderCurrency,
								"base_currency_code"=>$orderCurrency,
								"store_currency_code"=>$orderCurrency,
								"order_currency_code"=>$orderCurrency,
								"store_id"=>$orderStoreID,
								"order_id_lengow"=>$orderIdLengow
								);

		$this->store_id = $orderStoreID;


		$service = Mage::getModel('sales/service_quote', $this->_getQuote());
		$service->setOrderData($additionalData);

		$order = false;
		if(method_exists($service,"submitAll"))
		{
			
			$service->submitAll();
        	$order = $service->getOrder();
		}	
        else
        {
        	$order = $service->submit();
        }
        if ($order) 
        {
			//----------------------------- Etat ------------------------------
			$orderState = (string)$orderLw['State'];
			if($orderState == "processing"){
				$State = 'processing';
			}
			elseif ($orderState == "shipped") {
				$State = 'shipped';
			}
			else{
				$State = 'processing';
			}	

			$order=Mage::getModel('sales/order')->load($order->entity_id);
			
			//Si shipped, création de l'envoi
			if($State == 'shipped')
			{
				try {
					if($order->canInvoice()) {
						//Create invoice with pending status
						$invoiceId = Mage::getModel('sales/order_invoice_api')
							->create($order->getIncrementId(), array());

						$invoice = Mage::getModel('sales/order_invoice')
										->loadByIncrementId($invoiceId);

						//set invoice status "paid"
						$invoice->capture()->save();
					}
				}catch (Mage_Core_Exception $e) {
				// print_r($e);
				}
				try {
				    if($order->canShip()) {
				        //Create shipment
				        $shipmentid = Mage::getModel('sales/order_shipment_api')
				                        ->create($order->getIncrementId(), array());
				        //Add tracking information
				        $ship = Mage::getModel('sales/order_shipment_api')
				                        ->addTrack($order->getIncrementId(), array());       
				    }
				}catch (Mage_Core_Exception $e) {
				//
				}
			}
			//Passe les états
			//Tous n'existent pas sur les marketplace
			elseif($State == 'processing')
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
			elseif($State == 'new')
				$order->setState(Mage_Sales_Model_Order::STATE_NEW, true);
			elseif($State == 'pending')
				$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
			elseif($State == 'complete')
				$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
			elseif($State == 'closed')
				$order->setState(Mage_Sales_Model_Order::STATE_CLOSED, true);
			elseif($State == 'canceled')
				$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
			else
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
			$order->save();
			//-----------------------------------------------------------
			

            $this->_saveInvoice($order);
            
            //Set array with lengow ids
            $this->_ordersIdsImported[] = $orderIdLengow;


			$ConfigLengow = new Profileolabs_Lengow_Model_Manageorders_Config();
			$Lengow = new LengowConnector();
			$Lengow->lengow_token = $ConfigLengow->getApiKey();
			$Lengow->idClient = $ConfigLengow->getConfigDataExport('login');

            //Remontée de l'id interne Magento sous Lengow
			$array = array(
				'idClient' => (int)$ConfigLengow->getConfigDataExport('login'), 
				'idFlux' => $orderLw['IdFlux'],
				'Marketplace' => $orderLw['Marketplace'],
				'idCommandeMP' => $orderLw['IdOrder'],
				'idCommandeMage' => (string) $order->getIncrementId(),
				'statutCommandeMP' => $orderLw['State'],
				'statutCommandeMage' => $State,
				'idQuoteMage' => $quoteId,
				'Message' => 'Import depuis: '.$orderLw['Marketplace'].'<br/>idCommande: '.$orderIdLengow, 
				'type' => 'Magento'
			);

			$Lengow->callMethod('getInternalOrderId', $array);
			unset($array, $ConfigLengow, $Lengow);

            return $order;
            
        }
              
        return null;
        
	}
	
	protected function _saveOrder13(array $orderLw, $quoteId)
	{
		$orderIdLengow = (string)$orderLw['IdOrder'];
		$orderCurrency = (string)$orderLw['Currency'];
		$orderStoreID = (string)$orderLw['Store_Id'];
		
		$orderState = (string)$orderLw['State'];
		if($orderState == "Processing"){
			$State = 'processing';
		}
		elseif ($orderState == "Shipped") {
			$State = 'shipped';
		}
		else{
			$State = 'processing';
		}

		$additionalData = array("from_lengow"=>1,
								"marketplace_lengow"=>$orderLw['Marketplace'],
								"fees_lengow"=>(float)$orderLw['Fees'],
								"global_currency_code"=>$orderCurrency,
								"base_currency_code"=>$orderCurrency,
								"store_currency_code"=>$orderCurrency,
								"order_currency_code"=>$orderCurrency,
								"store_id"=>$orderStoreID,
								"order_id_lengow"=>$orderIdLengow,
								"state"=>$State,
								"status"=>$State
								);


		$this->store_id = $orderStoreID;

	
		$billing = $this->_getQuote()->getBillingAddress();
        $shipping = $this->_getQuote()->getShippingAddress();
		
		$this->_getQuote()->reserveOrderId();
        $convertQuote = Mage::getModel('sales/convert_quote');
       
        $order = $convertQuote->addressToOrder($shipping);
        
        $order->addData($additionalData);
        
        $order->setBillingAddress($convertQuote->addressToOrderAddress($billing));
        $order->setShippingAddress($convertQuote->addressToOrderAddress($shipping));

        $order->setPayment($convertQuote->paymentToOrderPayment($this->_getQuote()->getPayment()));

        foreach ($this->_getQuote()->getAllItems() as $item) {
            $orderItem = $convertQuote->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }
        

        Mage::dispatchEvent('checkout_type_onepage_save_order', array('order'=>$order, 'quote'=>$this->getQuote()));

        $order->place();
        
        $order->setCustomerId($this->_getQuote()->getCustomer()->getId());
        
        $order->setEmailSent(false);
        $order->save();

        Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order'=>$order, 'quote'=>$this->getQuote()));
        
		$this->_getQuote()->setIsActive(false);
        $this->_getQuote()->save();
        
		///////////////////////////////////////////////////////////////////////////
        
        if ($order) 
        {
           
            $this->_saveInvoice($order);
            
            $this->_ordersIdsImported[] = $orderIdLengow;   

            return $order;
            
        }


		$ConfigLengow = new Profileolabs_Lengow_Model_Manageorders_Config();
		$Lengow = new LengowConnector();
		$Lengow->lengow_token = $ConfigLengow->getApiKey();
		$Lengow->idClient = $ConfigLengow->getConfigDataExport('login');

        //Remontée de l'id interne Magento sous Lengow
		$array = array(
			'idClient' => (int)$ConfigLengow->getConfigDataExport('login'), 
			'idFlux' => $orderLw['IdFlux'],
			'Marketplace' => $orderLw['Marketplace'],
			'idCommandeMP' => $orderIdLengow,
			'idCommandeMage' => (string) $order->getIncrementId(),
			'statutCommandeMP' => $orderLw['State'],
			'statutCommandeMage' => $State,
			'idQuoteMage' => $quoteId,
			'Message' => 'Import depuis: '.$orderLw['Marketplace'].'<br/>idCommande: '.$orderIdLengow, 
			'type' => 'Magento'
		);

		$Lengow->callMethod('getInternalOrderId', $array);
			unset($array, $ConfigLengow, $Lengow);
        
        return null;
        
	}
	
	/**
	 * Create and Save invoice for the new order
	 * @param Mage_Sales_Model_Order $order
	 */
	protected function _saveInvoice($order)
	{
	 		Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order'=>$order, 'quote'=>$this->_getQuote()));
	 		
	 		if(!$this->getConfig()->createInvoice())
	 			return $this;
	 		
        	$path = Mage::getBaseDir()."/app/code/core/Mage/Sales/Model/Service/Order.php";
        	$invoice = false;
        	if(file_exists($path))
            	$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
            else
            	$invoice = $this->_initInvoice($order);
            	
            if($invoice)
            {
            	 $invoice->register();
            	 $invoice->getOrder()->setCustomerNoteNotify(false);
                 $invoice->getOrder()->setIsInProcess(true);
                 $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
            }
	}
	
	/**
	 * Initialize invoice
	 * @param Mage_Sales_Model_Order $order
	 * @return Mage_Sales_Model_Order_Invoice $invoice
	 */
	protected function _initInvoice($order)
	{
		
			$convertor  = Mage::getModel('sales/convert_order');
            $invoice    = $convertor->toInvoice($order);
			$update = false;
            $savedQtys = array();
            $itemsToInvoice = 0;

            foreach ($order->getAllItems() as $orderItem) {

                if (!$orderItem->isDummy() && !$orderItem->getQtyToInvoice() && $orderItem->getLockedDoInvoice()) {
                    continue;
                }

                if ($order->getForcedDoShipmentWithInvoice() && $orderItem->getLockedDoShip()) {
                    continue;
                }

                if (!$update && $orderItem->isDummy() && !empty($savedQtys) && !$this->_needToAddDummy($orderItem, $savedQtys)) {
                    continue;
                }
                $item = $convertor->itemToInvoiceItem($orderItem);

                if (isset($savedQtys[$orderItem->getId()])) {
                    $qty = $savedQtys[$orderItem->getId()];
                }
                else {
                    if ($orderItem->isDummy()) {
                        $qty = 1;
                    } else {
                        $qty = $orderItem->getQtyToInvoice();
                    }
                }
                $itemsToInvoice += floatval($qty);
                $item->setQty($qty);
                $invoice->addItem($item);
                
                 if ($itemsToInvoice <= 0){
                	Mage::throwException($this->__('Invoice without products could not be created.'));
                
            	}

            	
                
            }
            

           $invoice->collectTotals(); 
            
         return $invoice;
		
	}
	
	/**
	 * Get Helper
	 * @return Profileolabs_Lengow_Model_Manageorders_Helper_Data
	 */
	public function getHelper()
	{
		return Mage::helper('profileolabs_lengow');
	}
	
	public function getNbOrdersImported()
	{
		return $this->_nb_orders_imported;
	}

	public function getNbOrdersUpdated()
	{
		return $this->_nb_orders_updated;
	}

}