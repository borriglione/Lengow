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
        
        if(in_array("checkout_onepage_index",$handles) || in_array("onestepcheckout_index_index",$handles))
          $this->setPage('payment');
        
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
        if (!Mage::getStoreConfigFlag('lengow_export/general/active')) {
            return '';
        }
        
        if($this->getLogin() == "")
      return '';
        
        if($this->getPage() == "confirmation")
        {
          
          //Récupere l'id du caddie
          $quoteId = Mage::getSingleton('checkout/session')->getLastQuoteId();
          $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
          //Si l'id du caddie ne passe pas avec getLastQuoteId on passe par getQuoteId
          if(!$quoteId)
          {
            $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
          }
          //Si l'id n'existe toujours pas, on empeche l'affichage du tag
          if(!$quoteId)
              return '';
          $quote = Mage::getModel('sales/quote')->load($quoteId);
          
            $orders = Mage::getResourceModel('sales/order_collection')
                ->addAttributeToFilter('quote_id', $quoteId)
                ->load();
                
             foreach($orders as $order)
             { 
              $grandTotal = round($order->getBaseGrandTotal(),2);
              $incrementId = $order->getIncrementId();
              
              $productIds =array();
              foreach ($order->getAllItems() as $item)
              {
                $productIds[] = $item->getProductId();
              }
                  
                      $this->addText($this->writeTag($grandTotal,$quoteId,implode("|",$productIds)));
            // $this->addText($this->writeTag($grandTotal,$incrementId,implode("|",$productIds)));

             }
          
            $grandTotal = round($quote->getBaseGrandTotal(),2);
            $incrementId = "";
            
            $productIds =array();
            foreach ($quote->getAllItems() as $item)
            {
              $productIds[] = $item->getProductId();
            }
            
                if(isset($quoteId)) {
                    $quoteId = $quoteId;
                }
                else {
                    $quoteId = 0;
                }
                $this->addText($this->writeTag($grandTotal,$orderId,implode("|",$productIds), 'payment'));
                $this->addText($this->writeTag($grandTotal,$orderId,implode("|",$productIds)));
        }
        else
        {
          $this->addText($this->writeTag());
        }
        
        return parent::_toHtml();
    }
    
    protected function writeTag($amount="",$orderId="",$productIds="", $page = false)
    {
      if(!$page)
        $page = $this->getPage();
      return '<!-- Tag Lengow TagCapsule -->
              <script type="text/javascript">
              var page = "'.$page.'"; // #TYPE DE PAGE#
              var order_amt = "'.$amount.'"; // #MONTANT COMMANDE#
              var order_id = "'.$orderId.'"; // #ID COMMANDE#
              var product_ids = "'.$productIds.'"; // #ID PRODUCT#
              var ssl = "'.$this->useSSL().'"; // #SSL#
              </script>
              <script type="text/javascript" src="'.self::URI_TAG_CAPSULE.'?lengow_id='.$this->getLogin().'&idGroup='.$this->getGroup().'"></script>
              <!-- Tag Lengow TagCapsule -->
              ';
    }
}
