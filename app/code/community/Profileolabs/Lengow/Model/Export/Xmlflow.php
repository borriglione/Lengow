<?php
class Profileolabs_Lengow_Model_Export_Xmlflow
{
    protected $_xmlArray = array();
    
    protected $_backendCacheAttributes = array();
    
    protected $_memoryManager = null;
    
    public function __construct()
    {
    	$backendAttributes = array(
    			'cache_dir'                 => Mage::getBaseDir('cache'),
    			'hashed_directory_level'    => 1,
    			'hashed_directory_umask'    => 0777,
    			'file_name_prefix'          => 'mage',
    	);
    }
    
    public function getMemoryManager()
    {
    	if(is_null($this->_memoryManager))
    	{
    		$this->_memoryManager = Zend_Memory::factory('File',$this->_backendCacheAttributes);
    	}
    	 
    	return $this->_memoryManager;
    }
    
    public function _addEntries($entries)
    {
        $this->_xmlArray = $entries;
        return $this;
    }
    
    public function _addEntry($entry)
    {
        $this->_xmlArray[] = $entry;
        return $this;
    }

    public function getXmlArray()
    {
        return $this->_xmlArray;
    }

    
    public function getVersion()
    {
    	return Mage::getConfig()->getModuleConfig("Profileolabs_Lengow")->version;
    }
    
    public function createXml()
    {
        try {
                $xmlFlow = '<?xml version="1.0" encoding="utf-8"?>'.chr(10);
                $xmlFlow .= '<products count="'.count($this->_xmlArray).'" version="'.$this->getVersion().'" MageVersion="'.Mage::getVersion().'">'.chr(10); 

                
                foreach ($this->_xmlArray as $entry)
                {

                    $xmlFlow .= "<product>".chr(10);
                    
                    $xmlFlow .= $this->arrayToNode($entry);

                    $xmlFlow .= "</product>".chr(10);   
                }

                $xmlFlow .= "</products>".chr(10); 

                return $xmlFlow;
                    
        } catch (Exception $e) {
            return $this->getHelper()->__('Error in processing xml. %s',$e->getMessage());
        }
    }
    
    private function cleanLine($val) {
        $val = str_replace("|", " ", $val);
        $val = str_replace("\"", "'", $val);
        return str_replace(array("\n","\r","\n\r",chr(10),chr(13)), " ", $val);
    }

    private function cleanHeader($val) {
        $val = str_replace("-", "_", $val);
        return $this->cleanLine($val);
    }

    public function createCsv()
    {
        try {
            
                $i=0;
                $csvFlow = '';
                $csvFlowHead = '';
                $tab_entete = array();
                //recup des entetes
                foreach ($this->_xmlArray as $entry)
                {
                    foreach($entry AS $key => $val) {
                    	if(!in_array($key,$tab_entete))
                    	{
                    		$tab_entete[] = $key;
                    	}
                    }
                }
                //mise en forme
				foreach($tab_entete as $key => $val)
				{
					$csvFlowHead .= '"'.$this->cleanHeader($val).'"'."|";
				}
				$csvFlowHead = substr($csvFlowHead, 0, -1);
                //infos produits
                foreach ($this->_xmlArray as $entry)
                {
                    $csvFlowProd = '';
                    foreach($tab_entete AS $key => $val) {
                    	//echo $entry[$val]."<br>";
                    	if(isset($entry[$val]))
                    	{
                    		$csvFlowProd .= '"'.$this->cleanLine($entry[$val]).'"|';
                    	}
                    	else
                    	{
                    		$csvFlowProd .= '""|';
                    	}
                    }
                    $csvFlow .= substr($csvFlowProd,0,-1).chr(10);
                 }
                
				$csvFlow = $csvFlowHead.chr(10).$csvFlow;

                return $csvFlow;

        } catch (Exception $e) {
            return $this->getHelper()->__('Error in processing csv. %s',$e->getMessage());
        }
    }
    
    public function showVersion()
    {
    	echo  'ModuleVersion='.$this->getVersion().'<br/> MageVersion='.Mage::getVersion();
    }

   
    
    public function extractData($nameNode,$attributeCode,$product)
    {
    	
    		$_helper = Mage::helper('catalog/output');
    	
    		$data = $product->getData($attributeCode);
			
			$attribute = $product->getResource()->getAttribute($attributeCode);
			if($attribute)
			{				
				$data = $attribute->getFrontend()->getValue($product);
				$data = $_helper->productAttribute($product, $data, $attributeCode);
				
				if($nameNode == 'ecotaxe' && $attribute->getFrontendInput() == 'weee')
				{
					$weeeAttributes = Mage::getSingleton('weee/tax')->getProductWeeeAttributes($product);
					
					foreach ($weeeAttributes as $wa)
					{
						if($wa->getCode() == $attributeCode)
						{
							$data = round($wa->getAmount(),2);
							break;
						}
					}				
				}
			}
			
			//$_helper = Mage::helper('catalog/output');
			//if($nameNode == 'description' || $nameNode == 'short_description')
				//$data = $_helper->productAttribute($product, $data, $attributeCode);	
			
			//Synthetize it
			/*$method = "get".ucfirst($attributeCode);
			if(method_exists($product,$method))
				$data = $product->$method();*/

			//TODO remove this
			if($data== "No" || $data == "Non")
				$data = "";	

			//Exceptions data
			if($nameNode == 'shipping_delay' && empty($data))
				$data = $this->getConfig()->getConfigData('default_shipping_delay');
			
			if($nameNode == 'quantity')
				$data = round($data);
			
			return $data;
    }
    
     /**
     * Get singleton config for Export
     * @return Profileolabs_Lengow_Model_Export_Config
     */
    public function getConfig()
    {
    	return Mage::getSingleton('profileolabs_lengow/export_config');
    }
    
    protected function arrayToNode($entry)
    {
    	$node = "";
    	foreach ($entry as $key=>$value) {


    		if(is_array($value))
    		{
    			if(is_string($key))
    				$node.= $this->getNode($key, $this->arrayToNode($value),0);
    			elseif(is_string(($subKey =current($value))))
    				$node.= $this->getNode($subKey, $this->arrayToNode($value),0);
    			else
    				$node.= $this->arrayToNode($value);
    		}
    		else 
    			$node .= $this->getNode($key, $value);
    	}
    	
    	if($this->useZendMemory())
    		return $this->getMemoryManager()->create($node)->getRef();
    	
    	return $node;
    }
    
    protected function useZendMemory()
    {
    	return false;
    }
    
    protected function getNode($name,$value,$withCDATA = 1)
	{
		$value = $this->getHelper()->cleanNotUtf8($value);
		$openCDATA = "";
		$closeCDATA = "";
		if($withCDATA)
		{
			$openCDATA = "<![CDATA[";
			$closeCDATA = "]]>";
		}
		return "<{$name}>{$openCDATA}{$value}{$closeCDATA}</{$name}>".chr(10);
	}
	
	/**
     * Return Lengow Helper
     * @return Profileolabs_Lengow_Helper_Data
     */
    protected function getHelper()
    {
    	return Mage::helper('profileolabs_lengow');
    }
    
    
}