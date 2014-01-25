<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright  Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Trks\Form\Element;


use Zend\Form\Element\MultiCheckbox;
use Zend\Form\ElementInterface;
use Zend\Form\Exception\InvalidArgumentException;
use Zend\InputFilter\InputProviderInterface;
use Zend\Validator\Explode;
use Zend\Validator\InArray;
use Zend\Validator\ValidatorInterface;

class ButtonRow extends \Zend\Form\Element implements InputProviderInterface
{
    /**
     * Seed attributes
     *
     * @var array
     */
    protected $attributes = array(
        'type' => 'button_row',
    );

    /**
     * name, value, isSubmit, class
     * @var array
     */
    protected $buttons = array();


    /**
     * @var \Zend\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * name, value, isSubmit, class
     * @return array
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * @param  array $options
     * @return ButtonRow
     */
    public function setButtons(array $options)
    {
        $this->buttons = $options;

        // Update Explode validator haystack
        if ($this->validator instanceof Explode) {
            $validator = $this->validator->getValidator();
            if ($validator instanceof InArray) {
                $validator->setHaystack($this->getValueOptionsValues());
            }
        }

        return $this;
    }

    /**
     * Provide default input rules for this element
     *
     * @return array
     */
    public function getInputSpecification()
    {
        $spec = array(
            'name' => $this->getName(),
            'required' => true,
            'validators' => array(
                $this->getValidator()
            )
        );

        return $spec;
    }

    /**
     * Set options for an element. Accepted options are:
     * - buttons: name, value, isSubmit, class
     *
     * @param  array|\Traversable $options
     * @return ButtonRow|ElementInterface
     * @throws InvalidArgumentException
     */
    public function setOptions($options)
    {
        parent::setOptions($options);

        if (isset($this->options['buttons'])) {
            $this->setButtons($this->options['buttons']);
        }

        return $this;
    }

    /**
     * Set a single element attribute
     *
     * @param  string $key
     * @param  mixed  $value
     * @return ButtonRow|ElementInterface
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'buttons') {
            $this->setButtons($value);
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * Get validator
     *
     * @return ValidatorInterface
     */
    protected function getValidator()
    {
        if (null === $this->validator) {
            $inArrayValidator = new InArray(array(
                'haystack'  => $this->getValueOptionsValues(),
                'strict'    => false,
            ));
            $this->validator = new Explode(array(
                'validator'      => $inArrayValidator,
                'valueDelimiter' => null, // skip explode if only one value
            ));
        }
        return $this->validator;
    }

    /**
     * Get only the values from the options attribute
     *
     * @return array
     */
    protected function getValueOptionsValues()
    {
        $values = array();
        $options = $this->getButtons();
        foreach ($options as $key => $optionSpec) {
            $value = (is_array($optionSpec)) ? $optionSpec['value'] : $key;
            $values[] = $value;
        }
        return $values;
    }

    /**
     * Sets the value that should be selected.
     *
     * @param mixed $value The value to set.
     * @return ButtonRow
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
