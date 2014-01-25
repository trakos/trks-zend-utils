<?php
/**
 * Created by IntelliJ IDEA.
 * User: trakos
 * Date: 06.01.14
 * Time: 08:38
 */

namespace Trks\Model;


use Trks\Struct\ManyToManyJoinSpecification;

abstract class TrksAbstractRow
{
    /**
     * @param array $array
     *
     * @return void
     */
    abstract function exchangeArray($array);

    /**
     * @return array
     */
    abstract function toArray();


} 