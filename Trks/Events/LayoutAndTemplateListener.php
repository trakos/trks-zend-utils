<?php

namespace Trks\Events;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\View\Http\InjectTemplateListener;
use Zend\View\Model\ViewModel;

class LayoutAndTemplateListener extends InjectTemplateListener
{
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'injectTemplate'), -91);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'chooseLayout'), -101);
    }

    public function chooseLayout(MvcEvent $e)
    {
        $controller = $e->getTarget();
        $controller->layout('_layout/full');
    }

    public function injectTemplate(MvcEvent $e)
    {
        $model = $e->getResult();
        if (!$model instanceof ViewModel) {
            return;
        }

        $routeMatch = $e->getRouteMatch();
        $controller = $e->getTarget();
        if (is_object($controller)) {
            $controller = get_class($controller);
        }
        if (!$controller) {
            $controller = $routeMatch->getParam('controller', '');
        }

        $array = explode('\\', $controller);
        $module = $array[count($array) - 2];

        $controller = $this->deriveControllerClass($controller);

        $template   = $this->inflectName($module);

        if (!empty($template)) {
            $template .= '/';
        }
        $template  .= $this->inflectName($controller);

        $action     = $routeMatch->getParam('action');
        if ($action == 'not-found') {
            $model->setTemplate('error/404');
        } else {
            if (null !== $action) {
                $template .= '/' . $this->inflectName($action);
            }
            $model->setTemplate($template);
        }
    }
}