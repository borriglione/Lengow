<?php
/**
 * Shoppinflux
 * 
 * @category    Profileolabs
 * @package     Profileolabs_Lengow
 * @author		kassim belghait kassim@profileo.com
 */
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = Mage::getResourceModel('catalog/setup','profileolabs_lengow_setup');

$installer->startSetup();

$entityId = $installer->getEntityTypeId('catalog_product');
$attribute = $installer->getAttribute($entityId,'lengow_product');

if(!$attribute)
$installer->addAttribute('catalog_product', 'lengow_product', array(
    'type'              => 'int',
	'backend'           => '',
	'frontend'          => '',
	'label'        		=> 'Filtrer la présence dans le flux',
	'input'             => 'boolean',
	'global'            => 1,
	'visible'           => 1,
	'required'          => 0,
	'user_defined'      => 1,
	'default'           => 1,
	'searchable'        => 0,
	'filterable'        => 0,
	'comparable'        => 0,
	'visible_on_front'  => 1,
	'unique'            => 0,
	'used_in_product_listing' => 1
));

$installer->endSetup();