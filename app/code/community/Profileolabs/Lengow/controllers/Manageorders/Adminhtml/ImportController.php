<?php
/**
 * Lengow
 * @category   Lengow
 * @package    Profileolabs_Lengow_ManageOrders
 * @author kassim belghait
 */
class Profileolabs_Lengow_Manageorders_Adminhtml_ImportController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()
		->_setActiveMenu('lengow/manageorders/import')
		->_addBreadcrumb(Mage::helper('profileolabs_lengow')->__('Lengow import orders'), Mage::helper('profileolabs_lengow')->__('Lengow import orders'));

		return $this;
	}
	
	
	public function indexAction()
	{
		$this->_initAction()
		->renderLayout();
		
		return $this;
	}
	
	public function importOrdersAction()
	{
		try
		{
			
			if(!Mage::getSingleton('profileolabs_lengow/manageorders_config')->isEnabled())
				Mage::throwException(Mage::helper('profileolabs_lengow')->__("Le module n'est pas activé. Activez le dans la configuration du module."));
			
			
			error_reporting(E_ALL | E_STRICT);
			ini_set("display_errors", 1);	
			
			/* @var $model Profileolabs_Lengow_ManageOrders_Model_Order */
			
			
			$model = Mage::getModel('profileolabs_lengow/manageorders_order')->manageOrders();
			
			if($model->getNbOrdersImported() > 0)
				$this->_getSession()->addSuccess(Mage::helper('profileolabs_lengow')->__("%d orders are imported",$model->getNbOrdersImported()));
			if($model->getNbOrdersUpdated() > 0)
				$this->_getSession()->addSuccess(Mage::helper('profileolabs_lengow')->__("%d orders are updated",$model->getNbOrdersUpdated()));
			
			if($model->getResultSendOrder() != "")
			{
				$this->_getSession()->addSuccess(Mage::helper('profileolabs_lengow')->__("Status of order ids sended: %s",$model->getResultSendOrder()));
			
			}
		}
		catch(Exception $e)
		{

			$this->_getSession()->addError($e->getMessage());

		}
		
		$this->_redirect("*/*/index");
	}
	
}