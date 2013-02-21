<?php
/**
 * Lengow Log grid container
 * @category   Lengow
 * @package    Profileolabs_Lengow_ManageOrders
 * @author kassim belghait
 */
class Profileolabs_Lengow_Block_Manageorders_Adminhtml_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
	  parent::__construct();
	     
	    $this->_controller = 'manageorders_adminhtml_log';
	    $this->_blockGroup = 'profileolabs_lengow';
	    $this->_headerText = $this->__('Lengow log');
	
		$this->_removeButton('add');
		$this->_addButton("deleteAll",array(
											'label'   => Mage::helper('profileolabs_lengow')->__('Delete'),
								            'onclick' => "setLocation('{$this->getUrl('*/*/delete')}')",
								            'class'   => 'delete'
											));
	}
}