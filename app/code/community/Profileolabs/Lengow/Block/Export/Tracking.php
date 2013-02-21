<?php
/**
 * Profileolabs_Lengow Tracking Block
 *
 * @category   Profileolabs
 * @package    Profileolabs_Lengow
 * @author     kassim belghait <kassim@profileo.com>
 */
class Profileolabs_Lengow_Block_Export_Tracking extends Mage_Core_Block_Text
{
  const URI_TAG_CAPSULE = "https://tracking.lengow.com/tagcapsule.js";

    /**
     * Retrieve Lengow Account Identifier
     *
     * @return string
     */
    public function getLogin()
    {
        if (!$this->hasData('login')) {
            $this->setLogin(Mage::getStoreConfig('lengow_apik/general/login'));
        }
        return $this->getData('login');
    }
    
    /**
     * Retrieve Lengow Group Identifier
     *
     * @return string
     */
    public function getGroup()
    {
      if (!$this->hasData('group')) {
        $this->setGroup(Mage::getStoreConfig('lengow_apik/general/group'));
      }
      return $this->getData('group');
    }
    
     /**
     * Retrieve Page Type
     *
     * @return string
     */
    public function getPage()
    {
      if (!$this->hasData('page')) {
        
        $this->setPage('page');//by default

        $handles = $this->getLayout()->getUpdate()->getHandles();

        if(in_array("cms_index_index", $handles))
          $this->setPage('homepage');
        
        if(in_array("checkout_onepage_success",$handles))
          $this->setPage('confirmation');

        if(in_array("catalog_category_view",$handles))
          $this->setPage('listepage');

        if(in_array("checkout_cart_index",$handles))
          $this->setPage('basket');
        
      }
      return $this->getData('page');
    }
    
    /**
     * Retrieve if SSL is used for display tag capsule 
     *
     * @return string
     */
    public function useSSL()
    {
      if (!$this->hasData('use_ssl')) {
        $this->setUseSsl(Mage::getStoreConfig('lengow_export/general/ssl_tagcapsule'));
      }
      return $this->getData('use_ssl') == true ? "true" : "false" ;
    }

    /**
     * Prepare and return block's html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        //Sécurité
        if (!Mage::getStoreConfigFlag('lengow_export/general/active')) {
            return '';
        }

        if($this->getLogin() == "")
            return '';
        
        //Récupération de l'id caddie
        //Selon les versions et configs de Magento plusieurs méthodes peuvent échouées
        $quote_id = false;
        $quote_id = Mage::getSingleton('checkout/session')->getLastQuoteId();
        if(!$quote_id)
            $quote_id = Mage::getSingleton('checkout/session')->getQuoteId();
        if($quote_id)
        {
            //Création de l'objet Caddie
            $quote = Mage::getModel('sales/quote')->load($quote_id);
            //Extrait les produits
            $products_ids = $products_listing = array();
            $i = 1;
            foreach($quote->getAllVisibleItems() as $q)
            {
                $products_ids[] = $q->getId();
                $products_listing[] = 'i'.$i.'='.$q->getId().'&p'.$i.'='.$q->getPrice().'&q'.$i.'='.$q->getQty();
                $i++;
            }
            //Création des variables js
            $products_ids = implode('|', $products_ids);
            $products_listing = implode('&', $products_listing);

            $orders = Mage::getResourceModel('sales/order_collection')->addAttributeToFilter('quote_id', $quote_id)->load();
            foreach($orders as $o)
            {
              $order_id = $o->getIncrementId();
            }
        }

        //Récupération de l'identifiant de page
        $page = $this->getPage();
        //Gestion des différentes pages
        switch($page)
        {
            case 'homepage' :
                $this->addText($this->writeTag($page));
            break;
            case 'page' :
                $this->addText($this->writeTag($page));
            break;
            case 'listepage' :
                $handles = $this->getLayout()->getUpdate()->getHandles();
                foreach($handles as $h)
                {
                    if(substr($h, 0, 9) == 'CATEGORY_')
                    {
                        $category_id = str_replace('CATEGORY_', '', $h);
                    }
                }
                $this->addText($this->writeTag($page, '', '', '', '', $category_id));
            break;
            case 'basket' :
                $this->addText($this->writeTag($page, $quote->getBaseGrandTotal(), "", $products_ids, $products_listing));
            break;
            case 'confirmation' :
                $this->addText($this->writeTag('payment', $quote->getBaseGrandTotal(), $order_id, $products_ids, $products_listing));
                $this->addText($this->writeTag($page, $quote->getBaseGrandTotal(), $order_id, $products_ids, $products_listing));
            break;
        }

        return parent::_toHtml();
    }
    
    protected function writeTag($page = 'page', $amount = '', $order_id = '', $products_ids = '', $basket_products = '', $id_categorie = '')
    {
      return '<!-- Tag Lengow TagCapsule -->
              <script type="text/javascript">
                    var page = "'.$page.'"; // #TYPE DE PAGE#
                    var order_amt = "'.$amount.'"; // #MONTANT COMMANDE#
                    var order_id = "'.$order_id.'"; // #ID COMMANDE#
                    var product_ids = "'.$products_ids.'"; // #ID PRODUCT#
                    var basket_products = "'.$basket_products.'"; // #LISTING PRODUCTS IN BASKET#
                    var ssl = "'.$this->useSSL().'"; // #SSL#
                    var id_categorie = "'.$id_categorie.'" // #ID CATEGORIE#
                </script>
              <script type="text/javascript" src="'.self::URI_TAG_CAPSULE.'?lengow_id='.$this->getLogin().'&idGroup='.$this->getGroup().'"></script>
              <!-- End Tag Lengow TagCapsule -->
              ';
    }
}
