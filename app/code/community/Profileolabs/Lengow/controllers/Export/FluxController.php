<?php

class Profileolabs_Lengow_Export_FluxController extends Mage_Core_Controller_Front_Action {

  public function indexAction()
    {
      /**
       * Error reporting
       */
      error_reporting(E_ALL | E_STRICT);
      ini_set('display_errors', 1);
      set_time_limit(0);

      //ips serveur indexation lengow:
      $ipAuthorize = array('95.131.137.18','95.131.137.19','95.131.137.21','95.131.137.26','95.131.137.27','82.216.196.142','88.191.109.39');
      //ips autorisées dans la configuration du module
      $ip_conf = Mage::getStoreConfig('lengow_export/general/valid_ip');
      $tab_ip_conf = explode(';',$ip_conf);

      foreach ($tab_ip_conf as $ip) {
        if(trim($ip) != '')
        {
          $ipAuthorize[] = trim($ip);
        }
      }
      //print_r($ipAuthorize);exit;

      if(function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (array_key_exists('X-Forwarded-For', $headers)){
          $hostname_ip=$headers['X-Forwarded-For'];
        } else {
          $hostname_ip=$_SERVER["REMOTE_ADDR"];
        }
      }
      else {
        $hostname_ip=$_SERVER["REMOTE_ADDR"];
      }

      if(in_array($_SERVER['REMOTE_ADDR'], $ipAuthorize) || in_array($hostname_ip, $ipAuthorize))
      {

        $this->_configModel = Mage::getSingleton('profileolabs_lengow/export_config');
        /*
        if(isset($_GET['mode']) and ($_GET['mode'] == 'csv'))
        {
          $this->getResponse()
              ->setHttpResponseCode(200)
                  ->setHeader('Pragma', 'public', true)
                  ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)  
              ->setHeader('Content-type', 'text/plain; charset=UTF-8');
        }
        else
        {
          $this->getResponse()
              ->setHttpResponseCode(200)
                  ->setHeader('Pragma', 'public', true)
                  ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)  
              ->setHeader('Content-type', 'text/xml; charset=UTF-8');     
        }
*/
        if(!$this->_configModel->getAny('performances', 'usesavefile'))
        {
            if(!isset($_GET['mode']) || (strtolower($_GET['mode']) != 'csv' && strtolower($_GET['mode']) != 'xml'))
            {
                if($this->_configModel->getAny('performances', 'usexml'))
                  $this->getResponse()
                      ->setHttpResponseCode(200)
                          ->setHeader('Pragma', 'public', true)
                          ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)  
                      ->setHeader('Content-type', 'text/xml; charset=UTF-8');    
                else
                  $this->getResponse()
                      ->setHttpResponseCode(200)
                          ->setHeader('Pragma', 'public', true)
                          ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)  
                      ->setHeader('Content-type', 'text/plain; charset=UTF-8');
            }
            elseif(strtolower($_GET['mode']) == 'xml')
              $this->getResponse()
                  ->setHttpResponseCode(200)
                      ->setHeader('Pragma', 'public', true)
                      ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)  
                  ->setHeader('Content-type', 'text/xml; charset=UTF-8');    
            else
              $this->getResponse()
                  ->setHttpResponseCode(200)
                      ->setHeader('Pragma', 'public', true)
                      ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)  
                  ->setHeader('Content-type', 'text/plain; charset=UTF-8');
        }

        //echo "header done";
        //exit;

           try {
           
              $this->loadLayout(false);//\app\design\frontend\default\default\layout
              $this->renderLayout();
            } 
           catch (Exception $e) {
            
              Mage::throwException($e);
            }
           
          return $this;
      }
      else
      {
        echo "Unauthorised ip";
      }
    }
  
  
}
