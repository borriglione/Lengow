<?php

class Profileolabs_Lengow_Block_Export_Flow extends Mage_Core_Block_Template
{
    private $_productModel = null;
    private $_configModel = null;
    private $_configurableModel = null;
    private $_filename = 'lengow_feed';

    
    public function __construct()
    {
        $this->forceStore();
        $this->_productModel = Mage::getModel('catalog/product');
        $this->_configModel = Mage::getSingleton('profileolabs_lengow/export_config');
        $this->_configurableModel = Mage::getResourceSingleton('catalog/product_type_configurable');

        #Gestion de la mémoire
        $_memory = $this->_configModel->getAny('performances', 'memory');

        if($_memory === null || $_memory == 0 || $_memory == '')
            ini_set('memory_limit', '1024M');
        else
            ini_set('memory_limit', $_memory."M");
    }
    
    /**
    *   Retourne l'id du Store
    **/
    protected function _getStoreId()
    {
        return $this->_getStore()->getId();
    }
    
    /**
    *   Retourne un objet Store
    **/
    protected function _getStore()
    {

        $storeId = (int) $this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
        return Mage::app()->getStore($storeId);
    }
    
    /**
    *   Force l'initialisation du Store
    **/
    protected function forceStore()
    {
        if(( $storeId = (int) $this->getRequest()->getParam('store', 0) ) != 0)
        {
            Mage::app()->setCurrentStore(Mage::app()->getStore($storeId));
        }
    }

    /**
    *   Methode principale, génère le flux
    *
    **/
    protected function _toHtml($type = 'xml')
    {
        $storeId = $this->_getStoreId();
        $_excludes = array('media_gallery', 'tier_price');

        //Création de la liste des identifiants produits à exporter
        $products = $this->_getProductCollection($storeId)->getData();
        $product_list = $this->_collection2Array($products);
        
        //Mode size, retourne le nombre de produit total à exporter
        if(isset($_GET['mode']) && $_GET['mode'] == 'size')
            die((string)sizeof($product_list));

        unset($products);

        //Gestion des attributs à exporter
        $attrsFromConfig = $this->getAttributesFromConfig();
        $products_data = array();

        //Insertion des données
        foreach($product_list as $product)
        {
            //Init des variables
            $_parent = false;
            $_p = $this->_productModel->load($product);
            
            //Gestion des données parents
            //Si une configuration nécessite d'aller regarder les infos des produits parents on charge le père du produit
            if($this->_configModel->getAny('general', 'parentsimages'))
            {
                $parents = $this->_configurableModel->getParentIdsByChild($_p->getId());
                if(sizeof($parents) > 0)
                {
                    $_parent = $this->_productModel->load($parents[0]);
                    $parent = $_parent->getData();
                }
            }
            
            $p = $_p->getData();
            $_arr = array();

            //Insertion des données par défaut
            $_arr['sku'] = $p['sku'];
            $_arr['product-id'] = $p['entity_id'];
            $_arr['qty'] = (int) $p['stock_item']['qty'];
            $_arr = array_merge($_arr, $this->_getCategories($_p));
            $_arr = array_merge($_arr, $this->_getPrices($_p));
            $_arr = array_merge($_arr, $this->_getShippingInfo($_p));
            //Si on fusionne les images parents et si le produit dispose d'un produit parent
            if($this->_configModel->getAny('general', 'parentsimages') && $_parent !== false)
                $_arr = array_merge($_arr, $this->_getImages($p['media_gallery']['images'], $parent['media_gallery']['images']));
            else
                $_arr = array_merge($_arr, $this->_getImages($p['media_gallery']['images']));

            //Insertion des données configurées
            foreach($attrsFromConfig as $field => $attr)
            {

                if(isset($p[$field]) && !in_array($field, $_excludes))
                {
                    //Formater les données
                    if($this->_configModel->getAny('performances', 'formatdata'))
                        $_arr[$attr] = $this->_formatData($p[$field]);
                    else
                        $_arr[$attr] = $p[$field];
                }
            }   
            $products_data[] = $_arr;
        }

        //Init de l'objet de gestion du flux
        $feed = Mage::getModel('profileolabs_lengow/export_xmlflow');
        $feed->_addEntries($products_data);
        
        $usexml = false;

        //Gestion de XML / CSV 
        if(!isset($_GET['mode']) || (strtolower($_GET['mode']) != 'csv' && strtolower($_GET['mode']) != 'xml'))
        {
            if($this->_configModel->getAny('performances', 'usexml'))
                $usexml = true;
        }
        elseif(strtolower($_GET['mode']) == 'xml')
            $usexml = true;

        //Création du flux
        if($usexml)
        {
            $output = $feed->createXml();
            $this->_filename .= '.xml';
        }
        else
        {
            $output = $feed->createCsv();
            $this->_filename .= '.csv';
        }

        //Sauvegarde du flux si configurée
        if($this->_configModel->getAny('performances', 'usesavefile') && !isset($_GET['force']))
        {
            if($usexml)
                return "XML saved at ".$this->_writeFeed($output);
            else
                return "CSV saved at ".$this->_writeFeed($output);
        }

        //Retour classique du flux
        return $output;
    }
    

    protected function _getShippingInfo($product)
    {
        $data["shipping-name"]="";
        $data["shipping-price"]="";

        $carrier = $this->_configModel->getAny('donnees', 'default_shipping_method');
        if(empty($carrier))
            return $data;

        $carrierTab = explode('_',$carrier);
        list($carrierCode,$methodCode) = $carrierTab;       
        $data["shipping-name"] = ucfirst($methodCode);


        $shippingPrice = 0;     
        $countryCode = $this->_configModel->getAny('donnees', 'shipping_price_based_on');
        $shippingPrice = $this->helper('profileolabs_lengow')->getShippingPrice($product,$carrier,$countryCode);

        if(!$shippingPrice)
        {
            $shippingPrice = $this->_configModel->getAny('donnees', 'default_shipping_price');
        }

        $data["shipping-price"]=$shippingPrice;
            
        return $data;
    }

    protected function _getPrices($product)
    {
        Varien_Profiler::start("___ FLOW FINAL PRICE ___"); 
        $discountAmount = 0;
        $finalPrice = $product->getData('price');
        $priceBeforeDiscount = $product->getData('price');
        if($product->getSpecialPrice() > 0 && $product->getSpecialPrice() < $finalPrice)    
        {
            $finalPrice = $product->getSpecialPrice();
            $discountAmount = $product->getData('price') - $product->getSpecialPrice();
        }
        Varien_Profiler::stop("___ FLOW FINAL PRICE ___");  
                
        $product->setCalculatedFinalPrice($finalPrice);
        $product->setData('final_price',$finalPrice);

        $data["price-ttc"] = $finalPrice;
        $data["price-before-discount"] = $priceBeforeDiscount;
        $data["discount-amount"] = $product->getTypeId() != 'bundle' ?  $discountAmount : 0;
        $data["discount-percent"] = $this->getPercent($product);    

        $data["start-date-discount"] = "";
        $data["end-date-discount"] = "";    
        if($product->getSpecialFromDate() != "")
        {
            $data["start-date-discount"]  = $product->getSpecialFromDate();
        }
        if($product->getSpecialToDate() != "")
        {
            $data["end-date-discount"] = $product->getSpecialToDate();
        }

        return $data;
    }

    protected function _getCategories($product)
    {
        $catCollection = $product->getCategoryCollection();
        $categories = $catCollection->exportToArray();

        //Cherche la catégorie de plus haut niveau
        $currentLevel = 0;
        $category = false;
        foreach($categories as $c)
        {
            if($c['level'] > $currentLevel)
            {
                $currentLevel = $c['level'];
                $category = $c;
            }
            if($currentLevel > 4)
                break;
        }
        if($category['path'] != '')
            $categories = explode('/', $category['path']);
        else
            $categories = array();

        $data["category"] = "";
        $data["category-url"]= "";
        
        for($i=1;$i<=5;$i++)
        {
            $data["category-sub-".($i)]="";
            $data["category-url-sub-".($i)] = "";
        }
        $i = 0;
        $ariane = array();

        foreach($categories as $c)
        {
            $c = Mage::getModel('catalog/category')->load($c);
            if($c->getId() != 1)
            {
                if($i == 0)
                {
                    $data["category"]= $c->getName();
                    $data["category-url"] = $c->getUrl();
                    $ariane[] = $c->getName();
                }
                elseif($i <= 5){
                    $ariane[] = $c->getName();
                    $data["category-sub-".$i]= $c->getName();
                    $data["category-url-sub-".$i] = $c->getUrl();
                }
                $i++;
            }
        }
        $data['category-breadcrumb'] = implode(' > ', $ariane);
        unset($catCollection, $categories, $category, $ariane);
        
        return $data;
    }


    public function _getImages($images, $parentimages = false)
    {
        //Si des images du parent sont à exporter
        //On fusionne les deux listes, le reste du script fera le reste
        if($parentimages !== false)
        {
            $images = array_merge($parentimages, $images);
            $_images = array();
            $_ids = array();

            //Nettoyage du tableau
            foreach($images as $i)
            {
                if(!in_array($i['value_id'], $_ids))
                {
                    $_ids[] = $i['value_id'];
                    $_images[]['file'] = $i['file'];
                }
            }
            $images = $_images;
            unset($_images, $_ids, $parentimages);
        }

        $data = array();
        for($i = 1; $i < 6; $i++)
            $data['image-url-'.$i] = '';

        $c = 1;
        foreach($images as $i)
        {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$i['file'];
            $data['image-url-'.$c++] = $url;
            if($i == 6)
                break;
        }
        
        return $data;
        
    }
    /**
    * Retourne une collection de produit en fonction des parametres du BO
    *
    *   > Choix des status
    **/
    protected function _getProductCollection($storeId)
    {
        //Gestion des types par parametres get ou via la config
        if(isset($_GET['types']) && $_GET['types'] != '')
            $_types = explode(',', $_GET['types']);
        else
        {
            $_types = $this->_configModel->getAny('general', 'producttype');
            $_types = explode(',', $_types);
        }

        $products = $this->_productModel->getCollection()
        ->addStoreFilter($storeId) //Filtre le store
        ->addAttributeToFilter('type_id',array('in'=>$_types)) //Filtre les types de produits
        ->joinTable('cataloginventory/stock_item', 'product_id=entity_id', array('qty'=>'qty','is_in_stock' => 'is_in_stock'), $this->_getStockSQL(), 'inner');

        //Gestion des limit et offset        
        if(isset($_GET['limit']) && $_GET['limit'] != '')
        {
            if(isset($_GET['offset']) && $_GET['offset'] != '')
                $products->getSelect()->limit($_GET['limit'], $_GET['offset']);
            else
                $products->getSelect()->limit($_GET['limit']);
        }

        //Si on n'exporte que les produits selectionnés
        if($this->_configModel->isFilteredByAttribute())
        {  
            //Tester cette ligne sur différentes configs;
            //Il est possible que l'utilisation du catalogue à plat plante le script
            //if($this->getFlatHelper()->isEnabled($storeId))
            //Il semble aussi que selon la version le Magento la structure des flats products change
            $products->addAttributeToFilter('lengow_product',1);
        }

        //Filtre les produits non visibles
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);

        return $products;
    }
    
    /**
    * Retourne une clause WHERE pour filtrer les produits en stock si besoin
    **/
    private function _getStockSQL()
    {
        //Si on demande de filtrer les produits hors stock
        if(!$this->_configModel->isExportSoldout())
        {
            $_config = (int)Mage::getStoreConfigFlag(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
            $stockSQL = "({{table}}.is_in_stock = 1) "
            . " OR IF({{table}}.use_config_manage_stock=1," . $_config . ",{{table}}.manage_stock)=0";

            unset($_config);
            return $stockSQL;
        }

    }

    /**
    * Transforme une collection en array
    **/
    private function _collection2Array($col, $field = 'entity_id')
    {
        $ar = array();
        foreach($col as $c)
        {
            if(isset($c[$field]))
                $ar[] = $c[$field];
        }

        return $ar;
    }
    
    /**
     * Retourne les attributs matchés par le client
     */
    protected function getAttributesFromConfig($checkIfExist = false)
    {
        if(is_null($this->_attributes))
        {
            $attributes =  $this->_configModel->getMappingAllAttributes();
            
            if($checkIfExist)
            {
                $product = Mage::getModel('catalog/product');
                foreach ($attributes as $key=>$code)
                {
                    $attribute = $product->getResource()->getAttribute($code);
                    if($attribute instanceof Mage_Catalog_Model_Resource_Eav_Attribute && $attribute->getId() && $attribute->getFrontendInput() != 'weee')
                    {

                        $this->_attributes[$key] = $code; 
                    }
                }
            }
            else
             $this->_attributes = $attributes;  
                
        }
        return $this->_attributes;
    }
    
    /**
    * Retourne les attributs par défaut
    **/
    protected function getRequiredAttributes()
    {

        $requiredAttributes = array("sku"               =>"sku",
                                    "price"             =>"price",
                                    "image"             =>"image");
        
        return $requiredAttributes;
    
    }
    

    protected function _writeFeed($content)
    {
        $mode = "w+";
        $storeId = $this->_getStoreId();
        $storeCode = Mage::app()->getStore($storeId)->getCode();
        $dir = Mage::getBaseDir('media').DS."lengow".DS.$storeCode.DS;
        $file = new Varien_Io_File;
        $file->checkAndCreateFolder($dir);
        $file->cd($dir);
        $file->streamOpen($this->_filename, $mode);
        $file->streamLock();
        $file->streamWrite($content);
        $file->streamUnlock();
        $file->streamClose();

        return $dir.DS.$this->_filename;
    } 

    private function _formatData($str)
    {
        return nl2br(htmlentities($str));
    }

    /**
    * Retourne tous les attributs à exporter
    **/
    protected function getAllAttributes()
    {
        return array_merge($this->getAttributesFromConfig(true),$this->getRequiredAttributes());
    }
    
     /**
     * Retrieve Catalog Product Flat Helper object
     *
     * @return Mage_Catalog_Helper_Product_Flat
     */
    public function getFlatHelper()
    {
        return Mage::helper('catalog/product_flat');
    }
    
    
}
