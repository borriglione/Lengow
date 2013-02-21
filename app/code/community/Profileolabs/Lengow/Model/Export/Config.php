<?php
/**
 * Lengow Export Config Model
 * @category   Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 */
class Profileolabs_Lengow_Model_Export_Config extends Varien_Object
{

	
	/**
	 * Config key "Enable manage orders"
	 */
	const ENABLED = 'active';
	
	/**
	 * Config key "Filter by attribute"
	 */
	const FILTER_BY_ATTRIBUTE = 'filter_by_attribute';
	
	/**
	* Config key "Export soldout"
	*/
	const EXPORT_SOLDOUT = 'export_soldout';
	
	/**
	 * Config key "Filter by attribute"
	 */
	const LIMIT_PRODUCT = 'limit_product';
	
	/**
	 * Config key "Attributes kow"
	 */
	const ATTRIBUTES_KNOW = 'attributes_know';
	
	/**
	 * Config key "Attributes unkow"
	 */
	const ATTRIBUTES_UNKNOW = 'attributes_unknow';
	
	/*
	 * @var array $_attributesKnow
	 */
	protected $_attributesKnow = null;
	
	/**
	 * @var array $_attributesUnKnow
	 */
	protected $_attributesUnKnow = null;
	
	protected $_attributesSelected = null;
	
	/**
     *  Return config var
     *
     *  @param    string $key Var path key
     *  @param    int $storeId Store View Id
     *  @return	  mixed
     */
	public function getConfigData($key,$group = 'general', $storeId = null)
	{
		if (!$this->hasData($key)) {
            $value = Mage::getStoreConfig('lengow_export/'.$group.'/' . $key, $storeId);
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
	public function getConfigFlag($key,$group = 'general', $storeId = null)
	{
		if (!$this->hasData($key)) {
            $value = Mage::getStoreConfigFlag('lengow_export/'.$group.'/' . $key, $storeId);
            $this->setData($key, $value);
        }
        return $this->getData($key);
	}
	
	
	public function getAny($group, $key, $storeId = null)
	{
        return Mage::getStoreConfig('lengow_export/'.$group.'/' . $key, $storeId);
	}

	/**
	 * Retrieve if export is active
	 * 
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->getConfigFlag(self::ENABLED);
	}
	
	/**
	 * Retrieve if export is active
	 * 
	 * @return boolean
	 */
	public function isFilteredByAttribute()
	{
		return $this->getConfigFlag(self::FILTER_BY_ATTRIBUTE);
	}
	
	/**
	* Retrieve if export sold out products
	*
	* @return boolean
	*/
	public function isExportSoldout()
	{
		return $this->getConfigFlag(self::EXPORT_SOLDOUT);
	}
	
	/**
	 * Retrieve limit of product in query
	 * 
	 * @return int
	 */
	public function getLimitProduct()
	{
		return (int)$this->getConfigData(self::LIMIT_PRODUCT);
	}
	
	/**
	 * Return Attributes Knowed in array with key=>value
	 * key = node adn value = inner text
	 * @return array 
	 */
	public function getMappingAttributesKnow($storeId = null)
	{
		//if(is_null($this->_attributesKnow))
		if($this->_attributesKnow === null || !isset($this->_attributesKnow) || empty($this->_attributesKnow))
		{
			$this->_attributesKnow = Mage::getStoreConfig('lengow_export/attributes_know', $storeId);
		}
		
		return $this->_attributesKnow;
	}
	
	/**
	 * Return Attributes Unknowed in array with key=>value
	 * key = node adn value = inner text
	 * @param int $storeId
	 * @return array 
	 */
	public function getMappgingAttributesUnKnow($storeId = null)
	{
		//if(is_null($this->_attributesUnKnow))
		if($this->_attributesUnKnow === null || !isset($this->_attributesUnKnow) || empty($this->_attributesUnKnow))
		{
			$this->_attributesUnKnow = Mage::getStoreConfig('lengow_export/attributes_unknow', $storeId);
		}
		
		return $this->_attributesUnKnow;
	}
	
	public function getSelectedAttributes($storeId = null)
	{

		$tab = array();
		$this->_attributesSelected = array(); 
		//if(is_null($this->_attributesSelected))
		if($this->_attributesSelected === null || !isset($this->_attributesSelected) || empty($this->_attributesSelected))
		{
			$val = Mage::getStoreConfig('lengow_export/attributelist/attributes', $storeId);
			$tab = explode(',',$val);
			$this->_attributesSelected = array_flip($tab);
		}
		//print_r($this->_attributesSelected);
		if(!empty($tab))
		{
			foreach($this->_attributesSelected as $key => $value)
			{
				$this->_attributesSelected[$key] = $key;
			}
		}
		//print_r($this->_attributesSelected);
		return $this->_attributesSelected;
	}
	
	/**
	 * Return ALL Attributes Knowed and Unknowed in array with key=>value
	 * key = node adn value = inner text
	 * @return array 
	 * @param int $storeId
	 */
	public function getMappingAllAttributes($storeId = null)
	{
		//return array_merge($this->getMappingAttributesKnow($storeId),$this->getMappgingAttributesUnKnow($storeId));
		//print_r(array_merge($this->getMappingAttributesKnow($storeId),$this->getSelectedAttributes($storeId)));exit;
		return array_merge($this->getMappingAttributesKnow($storeId),$this->getSelectedAttributes($storeId));
	}
	

}