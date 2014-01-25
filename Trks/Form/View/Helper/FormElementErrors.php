<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Trks\Form\View\Helper;

use Traversable;
use Zend\Form\ElementInterface;
use Zend\Form\Exception;

class FormElementErrors extends \Zend\Form\View\Helper\FormElementErrors
{
    static $defaultPartial = null;

    /**
     * Render validation errors for the provided $element
     *
     * @param  ElementInterface $element
     * @param  array            $attributes
     *
     * @throws Exception\DomainException
     * @return string
     */
    public function render(ElementInterface $element, array $attributes = array())
    {
        if (!self::$defaultPartial) return parent::render($element, $attributes);

        $messages = $element->getMessages();
        if (empty($messages)) {
            return '';
        }
        if (!is_array($messages) && !$messages instanceof Traversable) {
            throw new Exception\DomainException(sprintf(
                '%s expects that $element->getMessages() will return an array or Traversable; received "%s"',
                __METHOD__,
                (is_object($messages) ? get_class($messages) : gettype($messages))
            ));
        }

        // Prepare attributes for opening tag
        $attributes = array_merge($this->attributes, $attributes);
        $attributesArray = $attributes;
        $attributes = $this->createAttributesString($attributes);
        if (!empty($attributes)) {
            $attributes = ' ' . $attributes;
        }

        // Flatten message array
        $escapeHtml      = $this->getEscapeHtmlHelper();
        $messagesToPrint = array();
        $self            = $this;
        array_walk_recursive($messages, function ($item) use (&$messagesToPrint, $escapeHtml, $self) {
            if (null !== ($translator = $self->getTranslator())) {
                $item = $translator->translate($item, $self->getTranslatorTextDomain());
            }
            $messagesToPrint[] = $escapeHtml($item);
        });

        if (empty($messagesToPrint)) {
            return '';
        }

        return $this->view->render(self::$defaultPartial, array(
            'escapedMessages'  => $messagesToPrint,
            'attributesString' => $attributes,
            'attributes'       => $attributesArray,
        ));
    }
}
