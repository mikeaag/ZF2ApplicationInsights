<?php
namespace ZF2ApplicationInsights\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Description of GetAppInsightsKey
 *
 * @author mike.gatward
 */
class GetAppInsightsKey extends AbstractHelper implements ServiceLocatorAwareInterface {
    
    private $serviceLocator = null;
    
    public function __invoke() {
        $config = $this->getConfig();
        return $config['zf2applicationinsights']['key'];
    }
    
    private function getConfig(){
        return $this->serviceLocator
                ->getServiceLocator()
                ->get('config');
    }
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;  
    }

    public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;  
        return $this;  
    }
}
