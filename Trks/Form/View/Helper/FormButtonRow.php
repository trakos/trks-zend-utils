<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Trks\Form\View\Helper;

use Trks\Form\Element\ButtonRow;
use \Zend\Form\ElementInterface;
use \Zend\Form\Element\MultiCheckbox as MultiCheckboxElement;
use \Zend\Form\Exception;
use Zend\Form\View\Helper\FormInput;

class FormButtonRow extends \Zend\Form\View\Helper\FormInput
{
    static $partial;

    /**
     * Form input helper instance
     *
     * @var FormInput
     */
    protected $inputHelper;

    /**
     * Invoke helper as functor
     *
     * Proxies to {@link render()}.
     *
     * @param  ElementInterface|null $element
     *
     * @return string|FormButtonRow
     */
    public function __invoke(ElementInterface $element = null)
    {
        if (!$element) {
            return $this;
        }

        return $this->render($element);
    }

    /**
     * Render a form <input> element from the provided $element
     *
     * @param  ElementInterface $element
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\DomainException
     * @return string
     */
    public function render(ElementInterface $element)
    {
        if (!$element instanceof ButtonRow) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires that the element is of type ButtonRow',
                __METHOD__
            ));
        }

        //$name = static::getName($element);

        $options = $element->getButtons();
        if (empty($options)) {
            throw new Exception\DomainException(sprintf(
                '%s requires that the element has "value_options"; none found',
                __METHOD__
            ));
        }

        $rendered = $this->renderButtons($element, $options);

        return $rendered;
    }

    /**
     * Render options
     *
     * @param  ButtonRow $element
     * @param  array     $buttons
     *
     * @throws \Exception
     * @return string
     */
    protected function renderButtons(ButtonRow $element, array $buttons)
    {
        if (!self::$partial) {
            throw new \Exception('No template for FormButtonRow set!');
        }

        return $this->view->render(self::$partial, array(
            'element' => $element,
            'buttons' => $buttons,
            'name'    => $element->getName(),
        ));
    }

    /**
     * Get element name
     *
     * @param  ElementInterface $element
     *
     * @throws Exception\DomainException
     * @return string
     */
    protected static function getName(ElementInterface $element)
    {
        $name = $element->getName();
        if ($name === null || $name === '') {
            throw new Exception\DomainException(sprintf(
                '%s requires that the element has an assigned name; none discovered',
                __METHOD__
            ));
        }
        return $name . '[]';
    }

    /**
     * Retrieve the FormInput helper
     *
     * @return FormInput
     */
    protected function getInputHelper()
    {
        if ($this->inputHelper) {
            return $this->inputHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->inputHelper = $this->view->plugin('form_input');
        }

        if (!$this->inputHelper instanceof FormInput) {
            $this->inputHelper = new FormInput();
        }

        return $this->inputHelper;
    }
}
