<?php

namespace Trks\View;

use Trks\Struct\MessagesStruct;
use Zend\Form\ElementInterface;
use Zend\Form\FormInterface;
use Zend\View\Renderer\PhpRenderer;

/**
 * This class exists only for the annotations below and code completion inside views, it is not in fact used anywhere!
 * @inheritdoc
 *
 * @package Trks\Build\View
 *
 * @method string|\Zend\Form\View\Helper\FormLabel formLabel(ElementInterface $element = null, $labelContent = null, $position = null)
 * @method string|\Zend\Form\View\Helper\FormElementErrors formElementErrors(ElementInterface $element = null, array $attributes = array())
 * @method string|\Zend\Form\View\Helper\Form form(FormInterface $form = null)
 * @method string|\Zend\Form\View\Helper\FormRow formRow(ElementInterface $element = null, $labelPosition = null, $renderErrors = null, $partial = null)
 * @method string|\Zend\Form\View\Helper\FormElement formElement(ElementInterface $element = null)
 * @method string|\Zend\Form\View\Helper\FormCollection formCollection(ElementInterface $element = null, $wrap = true)
 * @method string|\Trks\Form\View\Helper\FormButtonRow formButtonRow(ElementInterface $element = null)
 *
 * @method string|\Trks\View\Helper\Messages messages(MessagesStruct $messages = null)
 *
 */
class TrksPhpRenderer extends PhpRenderer
{
}