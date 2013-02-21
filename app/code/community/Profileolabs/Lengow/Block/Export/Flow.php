<?php

class Profileolabs_Lengow_Block_Export_Flow extends Mage_Core_Block_Template
{
    private $_productModel = null;
    private $_configModel = null;
    private $_configurableModel = null;
    private $_filename = 'lengow_feed';
    private $_tax = null;

    
    public function __construct()
    {
        #Init des variables
        $this->forceStore();
        $this->_productModel = Mage::getModel('catalog/product');
        $this->_configModel = Mage::getSingleton('profileolabs_lengow/export_config');
        $this->_configurableModel = Mage::getResourceSingleton('catalog/product_type_configurable');
        $this->_eavModel = Mage::getResourceModel('eav/entity_attribute_collection');

        #Récupération des taxes
        $taxRates = Mage::helper('tax/data')->getTaxRatesByProductClass();
        $taxRates = Zend_JSON::decode($taxRates);
        $this->_tax = array();
        foreach($taxRates as $k => $t)
        {
            $this->_tax[str_replace('value_', '', $k)] = (float) $t;
        }
        unset($taxRates, $t);

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
        $_excludes = array('media_gallery', 'tier_price', 'short_description', 'description');

        //Création de la liste des identifiants produits à exporter
        $products = $this->_getProductCollection($storeId);
        $product_list = $this->_collection2Array($products->getData(), 'sku');

        unset($products);
        

        //Mode size, retourne le nombre de produit total à exporter
        if(isset($_GET['mode']) && $_GET['mode'] == 'size')
            die((string)sizeof($product_list));


        //Gestion des attributs à exporter
        $attrsFromConfig = $this->getAttributesFromConfig();
        $products_data = array();
        $this->_attrs = array();

        //Insertion des données
        foreach($product_list as $sku)
        {
            $_arr = array();
            $parent = false;

            //Instance Product
            //Passe par le sku pour ne pas récupérer que le parent, passe ensuite par l'id pour récupérer toutes les infos
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            $product = Mage::getModel('catalog/product')->load($product->getId());
            
            //Remplace un simple getData()
            $data = $this->_productData($product);
            //Force l'id parent sur l'id courant
            $data['parent-id'] = $data['entity_id'];

            //Données des parents
            if($this->_configModel->getAny('donnees', 'parentsimages'))
            {
                //Charge les parents potentiels
                $parents = $this->_configurableModel->getParentIdsByChild($product->getId());

                if(sizeof($parents) > 0)
                {
                    foreach($parents as $parent)
                    {
                        $data['parent-id'] = $parent;
                        $parent = $this->_productModel->load($parent);
                        $pdata['media_gallery'] = $parent->getData('media_gallery');
                        break;
                    }
                    unset($parents);
                }
            }
            $qty = $product->getData('stock_item');

            //Insertion des données par défaut
            $_arr['sku'] = $data['sku'];
            $_arr['product-id'] = $data['entity_id'];
            $_arr['qty'] = (int) $qty['qty'];
            $_arr['parent-id'] = $data['parent-id'];
            $_arr['description'] = $data['description'];
            $_arr['short_description'] = $data['short_description'];
            unset($qty);

            $_arr = array_merge($_arr, $this->_getCategories($product));
            $_arr = array_merge($_arr, $this->_getPrices($data));
            $_arr = array_merge($_arr, $this->_getShippingInfo($product));

            //Images, gestion de la fusion parent / enfant
            if($this->_configModel->getAny('donnees', 'parentsimages') && $parent !== false)
                $_arr = array_merge($_arr, $this->_getImages($data['media_gallery']['images'], $pdata['media_gallery']['images']));
            else
                $_arr = array_merge($_arr, $this->_getImages($data['media_gallery']['images']));

            $_arr['product-url'] = str_replace('index.php/', '', Mage::getBaseUrl('web').$data['url_path']);
           
            //Boucle dans les attributs à exporter
            foreach($attrsFromConfig as $field => $attr)
            {

                if(isset($data[$field]) && !in_array($field, $_excludes) && !isset($_arr[$field]))
                {
                    //Formater les données
                    if($this->_configModel->getAny('performances', 'formatdata'))
                        $_arr[$attr] = $this->_formatData($data[$field]);
                    else
                        $_arr[$attr] = $data[$field];
                }
            }
            unset($data, $parent, $pdata);
            $products_data[] = $_arr;
        }
        unset($this->_attrs);

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
            unset($feed);
            $this->_filename .= '.xml';
        }
        else
        {
            $output = $feed->createCsv();
            unset($feed);
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
    
    protected function _productData($product)
    {
        $_data = array();
        //Récupère les informations du produit
        $data = $product->getData();
        //Supprime les informations générant des bugs
        unset($data['stock_item'], $data['is_in_stock'], $data['is_salable']);

        //Boucle dans les attributs
        foreach($data as $k => $d)
        {
            //Si le champs est potentiellement convertible en valeur
            if(is_numeric($d) && strpos($k, '_id') < 1 && strpos(' '.$k, 'price') == 0)
            {
                //Convertion de l'id en valeur
                if(!is_array($d) && !is_object($d) && $product->getAttributeText($k) != '')
                    $_data[$k] = $product->getAttributeText($k);
                else
                    $_data[$k] = $d;
            }
            else
                $_data[$k] = $d;
        }
        unset($data);

        //Ajoute les données levant des bugs
        $_data = array_merge($_data, array('stock_item' => $product->getData('stock_item'), 'is_in_stock' => $product->getData('is_in_stock'), 'is_salable' => $product->getData('is_salable')));

        return $_data;
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
        //Extraction des prix et taxes
        $price = (float) $product['price'];
        $special_price = (float) $product['special_price'];
        $special_from_date = $product['special_from_date'];
        $special_to_date = $product['special_to_date'];
        $tax = $this->_tax[$product['tax_class_id']];

        //Check les dates de promotion
        $time = time();
        if($time < $this->mage2time($special_from_date) || $time > $this->mage2time($special_to_date))
            $special_price = $price;
        
        //Si on force la tva
        if($this->_configModel->getAny('donnees', 'forcetax'))
        {
            $price = $price + ($price * ($tax / 100));
            if($special_price > 0)
                $special_price = $special_price + ($special_price * ($tax / 100));
        }

        //Calcul du montant de la réduction
        if($special_price == 0)
            $discount_amount = 0;
        else
        {
            $discount_amount = $price - $special_price;
            $discount_percent = ($discount_amount * 100) / $price;
        }
        
        //Si réduction
        if($special_price > 0)
        {
            $data["price-ttc"] = round($special_price, 2);
            $data["price-before-discount"] = round($price, 2);
            $data["discount-amount"] = round($discount_amount, 2);
            $data["discount-percent"] = round($discount_percent, 2);
        }
        else
        {
            $data["price-ttc"] = round($price, 2);
            $data["price-before-discount"] = round($price, 2);
            $data["discount-amount"] = '0';
            $data["discount-percent"] = '0';
        }
        $data["start-date-discount"] = $special_from_date;
        $data["end-date-discount"] = $special_to_date;

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
        ->addAttributeToSelect('sku')
        ->addAttributeToFilter('status',1)
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
            //Tester cette ligne sur différentes configs

            //1.5.1.0 Flat catalog
            if($this->getFlatHelper()->isEnabled($storeId) && Mage::getVersion() == '1.5.1.0'){  
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $query = 'SELECT attribute_id FROM eav_attribute WHERE attribute_code = "lengow_product"';
                $results = $readConnection->fetchAll($query);
                $attribute_id = $results[0]['attribute_id'];

                $products->joinTable('catalog_product_entity_int', 'entity_id=entity_id', array('value'), 'catalog_product_entity_int.attribute_id='.$attribute_id.' AND catalog_product_entity_int.value = 1', 'inner');
                unset($results, $query, $resource, $readConnection);       
            }
            //Cas classique
            else
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
    
    protected function datep($date)
    {
        $date = explode(' ', $date);
        $date = explode('-', $date[0]);
        $date = $date[1].'/'.$date[2].'/'.$date[0];
        return $date;
    }
    
    protected function mage2time($from)
    {
        $from = $this->datep($from);
        $from = strtotime($from);
        return $from;
    }
}
