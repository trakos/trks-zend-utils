<?php

namespace Trks\Mvc\Router;


use Zend\Mvc\Router\Http\Segment;
use Zend\Stdlib\RequestInterface;

class TrksRouter extends Segment
{
    /**
     * @param RequestInterface $request
     * @param null             $pathOffset
     * @param array            $options
     *
     * @return null|\Zend\Mvc\Router\Http\RouteMatch
     */
    public function match(RequestInterface $request, $pathOffset = null, array $options = array())
    {
        $result = parent::match($request, $pathOffset, $options);
        if ($result && $result->getParam('controller') && $result->getParam('module')) {
            $result->setParam('controller', $result->getParam('module') . '-' . $result->getParam('controller'));
        }
        return $result;
    }
} 