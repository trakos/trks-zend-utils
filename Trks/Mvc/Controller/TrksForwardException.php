<?php

namespace Trks\Mvc\Controller;

class TrksForwardException extends \Exception
{
    public $redirectRoute;
    public $redirectParams;
    public $redirectOptions;

    public function __construct($route = null, $params = array(), $options = array())
    {
        $this->redirectRoute = $route;
        $this->redirectParams = $params;
        $this->redirectOptions = $options;
        parent::__construct();
    }

}