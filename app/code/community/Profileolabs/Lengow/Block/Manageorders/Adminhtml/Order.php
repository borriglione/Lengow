<?php
/**
 * Lengow Service
 * @category   Lengow
 * @package    Profileolabs_Lengow_ManageOrders
 * @author kassim belghait
 */
class Profileolabs_Lengow_Block_Manageorders_Adminhtml_Order extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
	  parent::__construct();
	     
	    $this->_controller = 'manageorders_adminhtml_order';
	    $this->_blockGroup = 'profileolabs_lengow';
	    $this->_headerText = $this->__('Lengow orders');
	
		$this->_removeButton('add');
	}
}