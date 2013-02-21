<?php
class Profileolabs_Lengow_Model_System_Config_Source_Types extends Mage_Core_Model_Config_Data
{


    public function toOptionArray()
    {
        return array(
            array('value' => 'configurable', 'label'=>Mage::helper('adminhtml')->__('Configurable')),
            array('value' => 'simple', 'label'=>Mage::helper('adminhtml')->__('Simple')),
            array('value' => 'configurable,simple', 'label'=>Mage::helper('adminhtml')->__('Configurable, Simple')),
        );
    }
}
?>