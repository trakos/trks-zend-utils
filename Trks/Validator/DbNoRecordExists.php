<?php

namespace Trks\Validator;


use Zend\Validator\Db\NoRecordExists;

class DbNoRecordExists extends NoRecordExists
{

    public function __construct($options = null)
    {
        if (isset($options['adapter']) && is_string($options['adapter'])) {
            $options['adapter'] = \StarboundLog::getApplication()->getServiceManager()->get($options['adapter']);
        }
        parent::__construct($options);
    }
} 