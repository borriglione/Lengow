<?php
/**
 * Lengow
 * @category   Lengow
 * @package    Profileolabs_Lengow_ManageOrders
 * @author kassim belghait
 */
class Profileolabs_Lengow_Manageorders_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()
		->_setActiveMenu('lengow/manageorders/order')
		->_addBreadcrumb(Mage::helper('profileolabs_lengow')->__('Lengow orders'), Mage::helper('profileolabs_lengow')->__('Lengow orders'));

		return $this;
	}
	
	
	public function indexAction()
	{
		$this->_initAction()
		->renderLayout();
		
		return $this;
	}
	
	public function gridAction()
	{
		$this->getResponse()->setBody(
		$this->getLayout()->createBlock('profileolabs_lengow/manageorders_adminhtml_order_grid')->toHtml()
		);
		
		return $this;
	}
	
 	/**
     * Export order grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'orders_lengow.csv';
        $grid       = $this->getLayout()->createBlock('adminhtml/sales_order_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction()
    {
        $fileName   = 'orders_lengow.xml';
        $grid       = $this->getLayout()->createBlock('adminhtml/sales_order_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
	
	
}