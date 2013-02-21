<?php
/**
 * @category    Lengow
 * @package     Profileolabs_Lengow
 * @author kassim belghait
 * @deprecated deprecated since 0.1.1
 */
class Profileolabs_Lengow_Block_Export_Adminhtml_Process extends Mage_Adminhtml_Block_Abstract
{
	
	 /**
     * Flag for flow model
     * @var boolean
     */
    protected $_flowModelPrepared = false;
    /**
     * flow model instance
     * @var Profileolabs_Lengow_Model_Export_Flow
     */
    protected $_flowModel = null;
	
	public function _getFlowModel()
	{
		return $this->_flowModel;
	}
	
	 /**
     * Return batch model and initialize it if need
     * @return Profileolabs_Lengow_Model_Export_Flow
     */
    public function getFlowModel()
    {
         return $this->_prepareFlowModel()
            ->_getFlowModel();
    }
    
/**
     * Preparing flow model (initialization)
     * @return Profileolabs_Lengow_Block_Export_Adminhtml_Process
     */
    protected function _prepareFlowModel()
    {
        
        if ($this->_flowModelPrepared) {
            return $this;
        }
        $this->setShowFinished(true);
        /* @var $this->_flowModel Profileolabs_Lengow_Model_Export_Flow */

        $this->_flowModel = Mage::getSingleton('profileolabs_lengow/export_flow')->reset();;
        $storeId = $this->getRequest()->getParam('store',0);
        $this->_flowModel->setStoreId($storeId);
        $flowItemsCount =  $this->_flowModel->getProductCollection()->getSize();
		
        
        
        $this->_flowModel->setException(Mage::helper("dataflow")->__("%d produits trouvés.(en stock + hors stock)",$flowItemsCount));
        $soldOutTxt = "";
        if(!$this->_flowModel->getConfig()->isExportSoldout())
        {
        	$soldOutTxt = Mage::helper('profileolabs_lengow')->__('Seuls les produits en stock seront exportés');
        	$this->_flowModel->setException($soldOutTxt);
        }

        $numberOfRecords = $this->_flowModel->getCollectionByOffset(1)->count();
        $this->setNumberOfRecords($numberOfRecords);
        $this->setShowFinished(false);
    	$offsets = ceil((int)$flowItemsCount/ $this->_flowModel->getLimit());	
    	if($offsets == 0)
    		$offsets =1;
		
    	$this->_flowModel->setException(Mage::helper("dataflow")->__("La génération se fera en %d lot(s) de %d produit(s)",$offsets,$this->_flowModel->getLimit()));
    		
    	$this->setFlowItemsCount( $flowItemsCount);
        $this->setFlowConfig(
                    array(
                        'styles' => array(
                            'error' => array(
                                'icon' => Mage::getDesign()->getSkinUrl('images/error_msg_icon.gif'),
                                'bg'   => '#FDD'
                            ),
                            'message' => array(
                                'icon' => Mage::getDesign()->getSkinUrl('images/fam_bullet_success.gif'),
                                'bg'   => '#DDF'
                            ),
                            'loader'  => Mage::getDesign()->getSkinUrl('images/ajax-loader.gif')
                        ),
                        'template' => '<li style="#{style}" id="#{id}">'
                                    . '<img id="#{id}_img" src="#{image}" class="v-middle" style="margin-right:5px"/>'
                                    . '<span id="#{id}_status" class="text">#{text}</span>'
                                    . '</li>',
                        'text'     => $this->__('Etape <strong>%s/%s.  %s/%s</strong> produit(s)','#{offset}', $offsets, '#{updated}', '#{savedRows}'),
                        'successText'  => $this->__('<strong>%s</strong> produits exportés.', '#{updated}')
                    )
                );
                
                $importData = array();

                for($i=1;$i<=$offsets;$i++)
                {
                	$nbProducts =  $this->_flowModel->getLimit();
                	if($i==$offsets)
                		$nbProducts =  $this->_flowModel->getCollectionByOffset($i)->count();
                	
	                $importData[] = array("offset"=>$i,"nbProducts"=>$nbProducts,"store"=>$this->getRequest()->getParam('store'));
                }
                	
                $this->setImportData($importData);
        
        $this->_flowModelPrepared = true;
        return $this;
    }
    
    public function getExceptions()
    {
    	return $this->getFlowModel()->getExceptions();
    }
	
	
	
  /**
     * Generating form key
     * @return string
     */
    public function getFormKey()
    {
        return Mage::getSingleton('core/session')->getFormKey();
    }
    
    /**
     * Return a flow model config JSON
     * @return string
     */
    public function getFlowConfigJson()
    {
        return Zend_Json::encode(
            $this->getflowConfig()
        );
    }
    
	/**
     * Encoding to JSON
     * @param string $source
     * @return string JSON
     */
    public function jsonEncode($source)
    {
        return Zend_Json::encode($source);
    }
    
}