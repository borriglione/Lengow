<?php
/**
 * Lengow
 * @category   Lengow
 * @package    Profileolabs_Lengow
 * @author kassim belghait
 */
class Profileolabs_Lengow_Model_Export_Source_Attributes
{
    public function toOptionArray()
    {
    	$attributes= Mage::getSingleton('profileolabs_lengow/export_convert_parser_product')->getExternalAttributes();
    	array_unshift($attributes, array("value"=>"none","label"=>"Sélectionnez l'attribut à mapper"));
        return $attributes;        
    }
}
