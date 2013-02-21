<?php
/**
 * @category    Lengow
 * @package     Lengow_ManageOrders
 * @author 		kassim belghait
 */

$installerCustomer = new Mage_Customer_Model_Entity_Setup('profileolabs_lengow_setup');
/* @var $installerCustomer Mage_Customer_Model_Entity_Setup */

$installerCustomer->startSetup();

//$attribute   = Mage::getModel('eav/config')->getAttribute('customer', 'from_lengow');
$entityId = $installerCustomer->getEntityTypeId('customer');
$attribute = $installerCustomer->getAttribute($entityId,'from_lengow');
if(!$attribute)
{
	
	$installerCustomer->addAttribute('customer','from_lengow',array(
		'type'         => 'int',
	    'label'        => 'From Lengow',
	    'visible'      => true,
	    'required'     => false,
		'unique'       => false,
		'sort_order'   	   => 700,
	    'default'	   => 0,
		'input'		   => 'select',
		'source'       => 'eav/entity_attribute_source_boolean',
		));
		
	$usedInForms = array(
				'adminhtml_customer',
	        );
	
	$attribute   = Mage::getSingleton('eav/config')->getAttribute('customer', 'from_lengow');
	$attribute->setData('used_in_forms', $usedInForms);
	$attribute->setData('sort_order', 700);

	$attribute->save();

}

$installerCustomer->endSetup();

$installerSales = new Mage_Sales_Model_Mysql4_Setup('profileolabs_lengow_setup');
/* @var $installerSales Mage_Sales_Model_Mysql4_Setup */

$installerSales->startSetup();
//if(!Mage::getSingleton('eav/config')->getAttribute('order', 'from_lengow')->getId())
$entityId = $installerSales->getEntityTypeId('order');
$attribute = $installerSales->getAttribute($entityId,'from_lengow');
if(!$attribute)	
	$installerSales->addAttribute('order','from_lengow',array(
		'type'         => 'int',
	    'label'        => 'From Lengow',
	    'visible'      => true,
	    'required'     => false,
		'unique'       => false,
		'sort_order'   	   => 700,
	    'default'	   => 0,
		'input'		   => 'select',
		'source'       => 'eav/entity_attribute_source_boolean',
		'grid'		   => true,
		));

//if(!Mage::getSingleton('eav/config')->getAttribute('order', 'order_id_lengow')->getId())
$attribute = $installerSales->getAttribute($entityId,'order_id_lengow');
if(!$attribute)	
	$installerSales->addAttribute('order','order_id_lengow',array(
		'type'         => 'varchar',
	    'label'        => 'ID Order Lengow',
	    'visible'      => true,
	    'required'     => false,
		'unique'       => false,
		'sort_order'   	   => 705,
		'input'		   => 'text',
		'grid'		   => true,
		));

//if(!Mage::getSingleton('eav/config')->getAttribute('order', 'marketplace_lengow')->getId())
$attribute = $installerSales->getAttribute($entityId,'marketplace_lengow');
if(!$attribute)	
	$installerSales->addAttribute('order','marketplace_lengow',array(
		'type'         => 'varchar',
	    'label'        => 'Marketplace Lengow',
	    'visible'      => true,
	    'required'     => false,
		'unique'       => false,
		'sort_order'   	   => 710,
		'input'		   => 'text',
		'grid'		   => true,
		));

//if(!Mage::getSingleton('eav/config')->getAttribute('order', 'fees_lengow')->getId())
$attribute = $installerSales->getAttribute($entityId,'fees_lengow');
if(!$attribute)	
	$installerSales->addAttribute('order','fees_lengow',array(
		'type'         => 'decimal',
	    'label'        => 'Fees Lengow',
	    'visible'      => true,
	    'required'     => false,
		'unique'       => false,
		'sort_order'   	   => 720,
		'input'		   => 'text',
		'grid'		   => true,
		));

$installerSales->endSetup();

$installer = $this;

$installer->startSetup();

$installer->run(
			"CREATE TABLE IF NOT EXISTS `{$this->getTable('lengow_log')}` (
			`id` int(11) NOT NULL auto_increment,
			`date` timestamp NOT NULL default CURRENT_TIMESTAMP,
			`message` text NOT NULL,
			PRIMARY KEY  (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$installer->endSetup();

