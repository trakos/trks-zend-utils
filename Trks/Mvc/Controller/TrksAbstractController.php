<?php

namespace Trks\Mvc\Controller;


use Trks\Mvc\Controller\Plugin\TrksFlashMessenger;
use Trks\Struct\UsingFormResult;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

abstract class TrksAbstractController extends AbstractActionController
{
    abstract protected function createViewData();

    /**
     * @param $module
     * @param $controller
     * @param $action
     *
     * @throws TrksForwardException
     */
    abstract protected function isAllowed($module, $controller, $action);

    protected function init()
    {

    }
    private $_trksFlashMessenger;

    public function flashMessenger()
    {
        return $this->_trksFlashMessenger ? : ($this->_trksFlashMessenger = new TrksFlashMessenger());
    }

    /**
     * @param object $object
     * @param string $route
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array  $params
     *
     * @return UsingFormResult
     */
    protected function useAnnotationForm($object, $route, $action = null, $controller = null, $module = null, $params = array())
    {
        if ($action) $params['action'] = $action;
        if ($controller) $params['controller'] = $controller;
        if ($module) $params['module'] = $module;
        $result       = new UsingFormResult();
        $builder      = new AnnotationBuilder();
        $result->form = $builder->createForm($object);

        $request = $this->getRequest();
        if ($request && $request instanceof \Zend\Http\PhpEnvironment\Request) {
            $result->isPost = $request->isPost();
            if ($result->isPost) {
                $result->form->bind($object);
                $result->form->setData($request->getPost());
                $result->isValid = $result->form->isValid();
            }
        }

        $result->form->setAttribute('action', $this->url()->fromRoute($route, $params));
        $result->form->prepare();

        return $result;
    }

    public function onDispatch(MvcEvent $e)
    {
        $result = null;

        $routeMatch = $e->getRouteMatch();
        if (!$routeMatch) {
            throw new \Exception('no route matched!');
        }
        $module     = $routeMatch->getParam('module', 'not-found');
        $controller = explode('-', $routeMatch->getParam('controller', 'notFound'));
        $controller = end($controller);
        $action     = $routeMatch->getParam('action', 'not-found');
        if (!method_exists($this, static::getMethodFromAction($action))) {
            $action = 'not-found';
        }

        try {
            $this->isAllowed($module, $controller, $action);
            $this->init();
            $result = parent::onDispatch($e);
        } catch (TrksForwardException $exception) {
            return $this->redirect()->toRoute($exception->redirectRoute, $exception->redirectParams, $exception->redirectOptions);
        }

        $this->createViewData();
        return $result;
    }
} 