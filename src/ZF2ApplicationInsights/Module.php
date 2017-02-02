<?php

namespace ZF2ApplicationInsights;

use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{   
    private $serviceManager = null;
    
    public function getConfig(){
        return array(
            'zf2applicationinsights' => array(
                'key' => null,
                'trackRequests' => true
            )
        );
    }

    public function getViewHelperConfig()
    {
        return array(
            'invokables' => array(
                'getApplicationInsightsKey' => 'ZF2ApplicationInsights\View\Helper\GetApplicationInsightsKey'
            ),
        );
    }
    
    public function onBootstrap(MvcEvent $event){
        $this->serviceManager = $event->getTarget()->getServiceManager();
        $eventManager = $event->getApplication()->getEventManager();
        $this->registerEventListeners($eventManager);
    }
    
    private function registerEventListeners($eventManager){
        $context = $this;
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function($event) use ($context) {
            $context->exceptionHandler($event);    
        });
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, function($event) use ($context) {
            $context->exceptionHandler($event);    
        });
        $eventManager->attach(MvcEvent::EVENT_FINISH, function($event) use ($context){
            $context->trackRequest($event);
        });
    }
    
    public function exceptionHandler(MvcEvent $event){
        $exception = $event->getParam('exception');
        
        if($exception != NULL && $this->isKeyProvided()){
            $telemetryClient = $this->getTelemetryClient();
            $telemetryClient->trackException($exception);
        }
    }
    
    private function isKeyProvided(){
        return ($this->getInstrumentationKey() === null);
    }
    
    private function getIntrumentationKey(){
        return $this->getFromConfig('key');
    }
    
    private function getTelemetryClient(){
        $telemetryClient = new \ApplicationInsights\Telemetry_Client();
        $telemetryClient->getContext()->setInstrumentationKey($this->getIntrumentationKey());
        return $telemetryClient;
    }
    
    private function getFromConfig($key){
        $config = $this->serviceManager->get('config')['zf2applicationinsights'];
        $keys = explode('.', $key);
        foreach($keys as $key){
            $config = $config[$key];
        }
        return $key;
    }
    
    public function trackRequest(MvcEvent $event){
        $telemetryClient = $this->getTelemetryClient();
        
        if($this->isKeyProvided() && $this->isTrackRequests()){
            $telemetryClient->trackRequest(
                'pageview', 
                $this->getRequestUrl($event),
                $this->getRequestStartTime(),
                $this->getRequestDuration(),
                $this->getRequestResponseCode($event),
                $this->getIsRequestSuccessful($event)
            );
        }
        
        $telemetryClient->flush();
    }
    
    private function isTrackRequests(){
        return in_array($this->getFromConfig('trackRequests'), array('true', true), true);
    }
    
    private function getRequestUrl(MvcEvent $event){
        return $event->getRouter()->getRequestUri()->toString();
    }
    
    private function getRequestStartTime(){
        return defined(REQUEST_MICROTIME) ? REQUEST_MICROTIME : microtime(true);
    }
    
    private function getRequestDuration(){
        return (microtime(true) - $this->getRequestStartTime()) * 1000;
    }
    
    private function getRequestResponseCode(MvcEvent $event){
        return $event->getResponse()->getStatusCode();
    }
    
    private function getIsRequestSuccessful(MvcEvent $event){
        return $event->getResponse()->isSuccess();
    }
}   