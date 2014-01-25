<?php

namespace Trks\Singletons;


class TrksDbAdapter
{
    static protected $instance;

    static public function get()
    {
        return self::$instance ? : (self::$instance = new TrksDbAdapter());
    }

    protected $tableGateway;
    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $dbAdapter;

    public function __construct()
    {
        $this->dbAdapter = \StarboundLog::getApplication()->getServiceManager()->get('Zend\\Db\\Adapter\\Adapter');
    }

    public function fetchAll($q, $a)
    {
        $result = $this->dbAdapter->query($q, $a);
        return $result->toArray();
    }

    public function fetchRow($q, $a)
    {
        $result = $this->dbAdapter->query($q, $a);
        return $result->current();
    }

    public function fetchOne($q, $a)
    {
        $result = $this->dbAdapter->query($q, $a);
        return $result->current()[0];
    }

    public function fetchCol($q, $a)
    {
        $result = $this->dbAdapter->query($q, $a);
        $data = array();
        while ($row = $result->current())
        {
            $data[] = $row[0];
            $result->next();
        }
        return $data;
    }

    public function fetchAllPrototyped($q, $a, $prototype)
    {
        $result = $this->dbAdapter->query($q, $a);
        $result->setArrayObjectPrototype($prototype);
        $data = array();
        while ($row = $result->current())
        {
            $data[] = $row;
            $result->next();
        }
        return $data;
    }

    public function fetchRowPrototyped($q, $a, $prototype)
    {
        $result = $this->dbAdapter->query($q, $a);
        $result->setArrayObjectPrototype($prototype);
        return $result->current();
    }

} 