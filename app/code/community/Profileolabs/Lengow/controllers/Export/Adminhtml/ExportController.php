<?php
/**
 * Lengow
 * @category   Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 * @deprecated deprecated since 0.1.1
 */
class Profileolabs_Lengow_Export_Adminhtml_ExportController extends Mage_Adminhtml_Controller_action
{
	
		public function updateAction()
	    {
	    	
	        $this->loadLayout();
	        $this->renderLayout();
	        
	        return $this;
	    }
	    
	    /**
	     * Product grid for AJAX request
	     */
	    public function gridAction()
	    {
	        $this->loadLayout();
	        $this->getResponse()->setBody(
	            $this->getLayout()->createBlock('profileolabs_lengow/export_adminhtml_product_grid')->toHtml()
	        );
	    }
	    
	public function massPublishAction()
    {
        $productIds = (array)$this->getRequest()->getParam('product');
        $storeId    = (int)$this->getRequest()->getParam('store', 0);
        $publish     = (int)$this->getRequest()->getParam('publish');
       // die('P: '.$publish);
		//$entityTypeId = Mage::getSingleton('eav/config')->getEntityType('catalog_product')->getEntiyTypeId();
		$resource = Mage::getResourceModel('catalog/product');
		$entityTypeId  = $resource->getEntityType()->getId();

        try {
            foreach ($productIds as $productId) {
                $product = new Varien_Object(array('entity_id'=>$productId,
                							'id'=>$productId,
                							'entity_type_id'=>$entityTypeId,
                							'store_id'=>$storeId,
                							'lengow_product'=>$publish));
                $resource->saveAttribute($product,'lengow_product');
                
            }
            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) were successfully updated', count($productIds))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $e->getMessage().$this->__('There was an error while updating product(s) publication'));
        }

        $this->_redirect('*/*/update', array('store'=> $storeId));
    }
	

	protected function _getSession()
	{
		return Mage::getSingleton('adminhtml/session');
	}
		
}