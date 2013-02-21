<?php
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/app/Mage.php';

Mage::app();
Mage::getConfig()->init();


try {
	set_time_limit(0);
	Mage::getModel('profileolabs_lengow/manageorders_order')->manageOrders();


} catch (Exception $e) {

	Mage::printException($e);
}


die('Fin de process');