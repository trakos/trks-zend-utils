<?php

namespace Trks\Validator;


use Zend\Validator\Db\RecordExists;

class DbRecordExists extends RecordExists
{

    public function __construct($options = null)
    {
        if (isset($options['adapter']) && is_string($options['adapter'])) {
            $options['adapter'] = \StarboundLog::getApplication()->getServiceManager()->get($options['adapter']);
        }
        parent::__construct($options);
    }
} 