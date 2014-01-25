<?php

namespace Trks\Struct;


use Zend\Form\Form;

class UsingFormResult
{
    /**
     * @var bool
     */
    public $isPost;
    /**
     * @var bool
     */
    public $isValid;
    /**
     * @var Form
     */
    public $form;

    /**
     * @param Form $form
     * @param bool $isPost
     * @param bool $isValid
     */
    function __construct($form = null, $isPost = false, $isValid = false)
    {
        $this->form    = $form;
        $this->isPost  = $isPost;
        $this->isValid = $isValid;
    }

} 