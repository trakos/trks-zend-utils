<?php

namespace Trks\Validator;


use Zend\I18n\Validator\Alnum;
use Trks\Filter\AlnumLocaleIndependent as AlnumFilter;

class AlnumLocaleIndependent extends Alnum
{
    /**
     * Returns true if and only if $value contains only alphabetic and digit characters
     *
     * @param  string $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);
        if ('' === $value) {
            $this->error(self::STRING_EMPTY);
            return false;
        }

        if (null === static::$filter) {
            static::$filter = new AlnumFilter();
        }

        static::$filter->setAllowWhiteSpace($this->options['allowWhiteSpace']);

        if ($value != static::$filter->filter($value)) {
            $this->error(self::NOT_ALNUM);
            return false;
        }

        return true;
    }
} 