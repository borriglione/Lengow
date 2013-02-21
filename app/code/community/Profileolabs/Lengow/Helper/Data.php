<?php
/**
 * Lengow Helper
 * @category   Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 */
class Profileolabs_Lengow_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
     * Returns the node and children as an array
     *	values ares trimed
     *
     * @param bool $isCanonical - whether to ignore attributes
     * @return array|string
     */
	public function asArray(SimpleXMLElement $xml,$isCanonical = true)
    {
        $result = array();
        if (!$isCanonical) {
            // add attributes
            foreach ($xml->attributes() as $attributeName => $attribute) {
                if ($attribute) {
                    $result['@'][$attributeName] = trim((string)$attribute);
                }
            }
        }
        // add children values
        if ($xml->hasChildren()) {
            foreach ($xml->children() as $childName => $child) {
                if(!$child->hasChildren())
                	$result[$childName] = $this->asArray($child,$isCanonical);
            	else
       				$result[$childName][] = $this->asArray($child,$isCanonical); 
            }
        } else {
            if (empty($result)) {
                // return as string, if nothing was found
                $result = trim((string) $xml);
            } else {
                // value has zero key element
                $result[0] = trim((string) $xml);
            }
        }
        return $result;
    }
    
    
    public function log($message,$orderId = null)
    {
    	$modelLog = Mage::getModel('profileolabs_lengow/manageorders_log');
    	
    	$modelLog->log($message,$orderId);

    	return $this;
    }
    
    public function isUnderVersion14()
    {
	    $currentVersion = Mage::getVersion();
		if (version_compare($currentVersion, '1.4.0')<0)
	   		return true;
		   
		return false;

    }
    
    public function getShippingPrice($product,$carrierValue,$countryCode="FR")
    {
    	$carrierTab = explode('_',$carrierValue);
    	list($carrierCode,$methodCode) = $carrierTab;
    	$shipping = Mage::getModel('shipping/shipping');
    	$methodModel = $shipping->getCarrierByCode($carrierCode);
    	if($methodModel)
    	{
    		$result = $methodModel->collectRates($this->getRequest($product,$countryCode="FR"));
    		if($result != NULL)
    		{
	    		if($result->getError())
	    		{
	    			Mage::logException(new Exception($result->getError()));
	    		}
	    		else
	    		{
	    			foreach($result->getAllRates() as $rate)
	    			{
	    				return $rate->getPrice();
	    			}
	    		}
    		}
    		else
    		{
    			//echo "no result";exit;
    			return false;
    		}
    	}
    	
    	return false;
    }
    
    protected function getRequest($product,$countryCode="FR")
    {
    	/** @var $request Mage_Shipping_Model_Rate_Request */
        $request = Mage::getModel('shipping/rate_request');
        $storeId = $request->getStoreId();
    	if (!$request->getOrig()) {
            $request
                ->setCountryId($countryCode)
                ->setRegionId("")
                ->setCity("")
                ->setPostcode("")
                ;
        }
        
        $item = Mage::getModel('sales/quote_item');
        $item->setStoreId($storeId);
        $item->setOptions($product->getCustomOptions())
            ->setProduct($product);
            
        $request->setAllItems(array($item));
        
        $request->setDestCountryId($countryCode);
        $request->setDestRegionId("");
        $request->setDestRegionCode("");
        $request->setDestPostcode("");
        $request->setPackageValue($product->getPrice());

        $request->setPackageValueWithDiscount($product->getFinalPrice());
        $request->setPackageWeight($product->getWeight());
        $request->setFreeMethodWeight(0);
        $request->setPackageQty(1);
        
        $request->setStoreId(Mage::app()->getStore()->getId());
        $request->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $request->setBaseCurrency(Mage::app()->getStore()->getBaseCurrency());
        $request->setPackageCurrency(Mage::app()->getStore()->getCurrentCurrency());
        
        //$request->setLimitCarrier($limitCarrier);
        
        return $request;
    }
    
    public function getFilesGenerated()
    {
    	$dirStores = array();
    	$links = array();
    	if(!is_dir(Mage::getBaseDir('media').DS.'lengow'))
    	{
    		mkdir(Mage::getBaseDir('media').DS.'lengow');
    	}
	    if ($handle = opendir(Mage::getBaseDir('media').DS.'lengow'.DS)) {
		
		    /* This is the correct way to loop over the directory. */
		    while (false !== ($file = readdir($handle))) {
		    	if($file == "." || $file == "..")
		    		continue;
		       $dirStores[] = $file;
		    }
		
		    closedir($handle);
		}
		
		foreach($dirStores as $store)
		{
			$links[] = Mage::getBaseUrl('media')."lengow/".$store."/flow.xml";
		}
		return $links;
    }
    
    /**
     * Clean None utf-8 characters
     * @param string $value
     * @return string $value
     */
    public function cleanNotUtf8($value)
    {
    	
    	$value = Mage::helper('core/string')->cleanString($value);

    	
    	//reject overly long 2 byte sequences, as well as characters above U+10000 and replace with blank
		$value = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
		 '|[\x00-\x7F][\x80-\xBF]+'.
		 '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
		 '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
		 '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
		 '', $value );

		//reject overly long 3 byte sequences and UTF-16 surrogates and replace with blank
		$value = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]'.
		 '|\xED[\xA0-\xBF][\x80-\xBF]/S','', $value );
		
		/*$value = preg_replace("/([\x80-\xFF])/e",
				"chr(0xC0|ord('\\1')>>6).chr(0x80|ord('\\1')&0x3F)",
				$value);*/
		
		
		$value = str_replace(chr(31),"", $value);
		$value = str_replace(chr(30),"", $value);
		$value = str_replace(chr(29),"", $value);
		$value = str_replace(chr(28),"", $value);
		
		
		//$value = iconv("UTF-8","UTF-8//IGNORE",$value);

		
		return $value;
    }
    
    function _convert($content) {
    	if(!mb_check_encoding($content, 'UTF-8')
    			OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {
    
    		$content = mb_convert_encoding($content, 'UTF-8');
    
    		if (mb_check_encoding($content, 'UTF-8')) {
    			// log('Converted to UTF-8');
    		} else {
    			// log('Could not converted to UTF-8');
    		}
    	}
    	return $content;
    }
    
 	public function getAttributesConfigurable($product)
    {
		
    		$p = $product;
    		if(is_object($product))
    			$product = $product->getId();
    	
    		if(intval($product) >0)
    		{
	    		$resource = Mage::getSingleton('core/resource');
				$read = $resource->getConnection('catalog_read');
				
				$superAttributeTable = $resource->getTableName('catalog_product_super_attribute');
				$eavAttributeTable = $resource->getTableName('eav/attribute');
				
				$select = $read->select('attribute_id')
				->from($superAttributeTable)
				->join(array("att"=>$eavAttributeTable),
			                $superAttributeTable.'.attribute_id=att.attribute_id',
							array("attribute_code"=>"attribute_code"))
				->where("product_id = ".$product);
				
				
				//$result = $read->fetchAll($select);
				
				try
				{
					$result = $read->fetchAll($select);
	    		}
				catch(Exception $e)
				{
					print_r($p->debug());
					print_r($p);
					die($select);
				}
				
				return $result;
    		}
    		else
    		{
				//print_r($p->debug());
    			//die("id:".$product);
    			return array();
    		}
    }
    
}