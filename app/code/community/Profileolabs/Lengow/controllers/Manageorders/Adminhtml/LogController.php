<?php
/**
 * Lengow
 * @category   Lengow
 * @package    Profileolabs_Lengow_ManageOrders
 * @author kassim belghait
 */
class Profileolabs_Lengow_Manageorders_Adminhtml_LogController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()
		->_setActiveMenu('lengow/manageorders/log')
		->_addBreadcrumb(Mage::helper('profileolabs_lengow')->__('Lengow orders'), Mage::helper('profileolabs_lengow')->__('Lengow orders'));

		return $this;
	}
	
	
	public function indexAction()
	{
		$this->_initAction()
		->renderLayout();
		
		return $this;
	}
	
	public function deleteAction()
	{
		$collection = Mage::getModel('profileolabs_lengow/manageorders_log')->getCollection();
		foreach($collection as $log)
			$log->delete();
			
		$this->_getSession()->addSuccess(Mage::helper('profileolabs_lengow')->__("Log is empty."));
		
		$this->_redirect('*/*/index');
			
	}
	
	public function gridAction()
	{
		$this->getResponse()->setBody(
		$this->getLayout()->createBlock('profileolabs_lengow/manageorders_adminhtml_log_grid')->toHtml()
		);
		
		return $this;
	}
	
}