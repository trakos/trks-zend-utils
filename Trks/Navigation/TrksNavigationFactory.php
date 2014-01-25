<?php

namespace Trks\Navigation;


use Zend\Mvc\Router\RouteInterface;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\Navigation\Service\AbstractNavigationFactory;
use Zend\Navigation\Service\DefaultNavigationFactory;
use Zend\Permissions\Acl\Resource\GenericResource;

abstract class TrksNavigationFactory extends AbstractNavigationFactory
{
    /**
     * @param $module
     * @param $controller
     * @param $action
     *
     * @return string|GenericResource
     */
    abstract function getResourceForRoute($module, $controller, $action);

    /**
     * @param array                                $pages
     * @param RouteMatch                           $routeMatch
     * @param \Zend\Mvc\Router\RouteStackInterface $router
     *
     * @return mixed
     */
    protected function injectComponents(array $pages, RouteMatch $routeMatch = null, RouteStackInterface $router = null)
    {
        foreach ($pages as &$page) {
            $page['resource'] = $this->getResourceForRoute($page['module'], $page['controller'], $page['action']);
            unset($page);
        }
        return parent::injectComponents($pages, $routeMatch, $router);
    }

} 