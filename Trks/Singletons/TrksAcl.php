<?php

namespace Trks\Singletons;


use Zend\Permissions\Acl\Acl;

class TrksAcl
{
    static private $acl;

    static public function getAcl()
    {
        return self::$acl ? : (self::$acl = new Acl());
    }

    static public function register($config)
    {
        $acl = self::getAcl();
        foreach ($config['roles'] as $role) {
            $acl->addRole($role['name'], $role['parents']);
            if (isset($role['allowed'])) {
                foreach ($role['allowed'] as $resourceName) {
                    if (!$acl->hasResource($resourceName)) {
                        $acl->addResource(new \Zend\Permissions\Acl\Resource\GenericResource($resourceName));
                    }
                    $acl->allow($role['name'], $resourceName);
                }
            }
            if (isset($role['denied'])) {
                foreach ($role['denied'] as $resourceName) {
                    if (!$acl->hasResource($resourceName)) {
                        $acl->addResource(new \Zend\Permissions\Acl\Resource\GenericResource($resourceName));
                    }
                    $acl->deny($role['name'], $resourceName);
                }
            }
        }
    }

    static public function isRoleAllowedToRouteInConfig($config, $roleName, $module, $controller, $action)
    {
        if ($action == 'not-found') return true;
        $resource = self::findRouteResourceInConfig($config, $module, $controller, $action);
        if (!$resource) return false;
        return self::getAcl()->isAllowed($roleName, $resource);
    }

    static public function findRouteResourceInConfig($config, $module, $controller, $action)
    {
        foreach ($config['route_permissions'] as $routePermission) {
            if (
                strcasecmp($routePermission['module'], $module) == 0
                && (
                    (!$routePermission['controller'] && (!$routePermission['action'] || strcasecmp($routePermission['action'], $action) == 0))
                    || (strcasecmp($routePermission['controller'], $controller) == 0 && (!$routePermission['action'] || strcasecmp($routePermission['action'], $action) == 0))
                )
            ) {
                return $routePermission['resource'];
            }
        }
        return null;
    }
} 