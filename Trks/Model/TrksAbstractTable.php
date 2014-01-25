<?php
/**
 * Created by IntelliJ IDEA.
 * User: trakos
 * Date: 06.01.14
 * Time: 08:29
 */

namespace Trks\Model;


use Trks\Struct\ManyToManyJoinSpecification;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;

abstract class TrksAbstractTable
{
    /**
     * @var AbstractTableGateway
     */
    protected $tableGateway;
    /**
     * @var AbstractTableGateway[]
     */
    protected $manyToManyJoinTableGateways = [];

    public function __construct()
    {
        $resultSetPrototype = new \Zend\Db\ResultSet\ResultSet();
        $resultSetPrototype->setArrayObjectPrototype($this->getPrototype());
        $this->tableGateway = new \Zend\Db\TableGateway\TableGateway($this->getTableName(), $this->getDbAdapter(), null, $resultSetPrototype);
        foreach ($this->getManyToManyRelations() as $relation) {
            $this->manyToManyJoinTableGateways[$relation->joinTableName] = new \Zend\Db\TableGateway\TableGateway($relation->joinTableName, $this->getDbAdapter());
        }
    }

    /**
     * @return string
     */
    abstract protected function getTableName();

    /**
     * @return \Zend\Db\Adapter\Adapter
     */
    abstract protected function getDbAdapter();

    /**
     * @return object
     */
    abstract protected function getPrototype();

    /**
     * @return string
     */
    abstract protected function getPrimaryKeyName();

    /**
     * @return ManyToManyJoinSpecification[]
     */
    abstract protected function getManyToManyRelations();

    /**
     * @param $q
     * @param $a
     *
     * @return object[]
     */
    public function fetchAll($q, $a)
    {
        return \Trks\Singletons\TrksDbAdapter::get()->fetchAllPrototyped($q, $a, $this->getPrototype());
    }

    /**
     * @param $q
     * @param $a
     *
     * @return object|null
     */
    public function fetchRow($q, $a)
    {
        return \Trks\Singletons\TrksDbAdapter::get()->fetchRowPrototyped($q, $a, $this->getPrototype());
    }

    /**
     * @param array $filterArray
     *
     * @return object[]
     */
    public function filterRows(array $filterArray)
    {
        $resultSet = $this->tableGateway->select($filterArray);
        $data      = array();
        while ($row = $resultSet->current()) {
            $data[] = $row;
            $resultSet->next();
        }
        return $data;
    }

    /**
     * @param array $filterArray
     *
     * @return object|null
     */
    public function filterRowsGetFirst(array $filterArray)
    {
        $rowSet = $this->tableGateway->select($filterArray);
        $row    = $rowSet->current();
        if (!$row) {
            return null;
        }
        return $row;
    }

    /**
     * @return object[]
     */
    public function getAllRows()
    {
        return $this->filterRows(array());
    }

    /**
     * @param $id
     *
     * @return object|null
     */
    public function getRow($id)
    {
        return $this->filterRowsGetFirst(array(
            $this->getPrimaryKeyName() => $id
        ));
    }

    /**
     * @param TrksAbstractRow $row
     *
     * @throws \Exception
     * @return int Updated or inserted id.
     */
    public function saveRow(TrksAbstractRow $row)
    {
        $data = $row->toArray();
        unset($data[$this->getPrimaryKeyName()]);

        $id = (int)$row->{$this->getPrimaryKeyName()};
        if ($id == 0) {
            $this->tableGateway->insert($data);
            $row->{$this->getPrimaryKeyName()} = (int)$this->tableGateway->lastInsertValue;
        } else {
            if ($this->getRow($id)) {
                $this->tableGateway->update($data, array($this->getPrimaryKeyName() => $id));
            } else {
                throw new \Exception('Row id does not exist');
            }
        }
        $id = (int)$row->{$this->getPrimaryKeyName()};


        foreach ($this->getManyToManyRelations() as $relation) {

            $joinTableGateway = $this->manyToManyJoinTableGateways[$relation->joinTableName];
            /* @var int[] $foreignIdsArray */
            $foreignIdsArray = $row->{$relation->joinTableColumnNameInEntityClass};
            $joinTableGateway->delete(array(new NotInPredicate($relation->joinTableColumnOtherName, $foreignIdsArray)));

            $existingForeignIds = $this->_getForeignIdsFor($joinTableGateway, $relation->joinTableColumnThisName, $relation->joinTableColumnOtherName, $id);
            $foreignIdsArray    = array_diff($foreignIdsArray, $existingForeignIds);
            foreach ($foreignIdsArray as $foreignId) {
                $joinTableGateway->insert(array(
                    $relation->joinTableColumnThisName  => $id,
                    $relation->joinTableColumnOtherName => $foreignId,
                ));
            }

        }

        return $id;
    }

    protected function _getForeignIdsFor(AbstractTableGateway $joinTableGateway, $joinThisIdColumn, $joinForeignIdColumn, $rowPrimaryKeyValue)
    {
        $resultSet = $joinTableGateway->select(array($joinThisIdColumn => $rowPrimaryKeyValue));
        $data      = array();
        while ($row = $resultSet->current()) {
            $data[] = $row->$joinForeignIdColumn;
            $resultSet->next();
        }
        return $data;
    }

    /**
     * @param int $id
     */
    public function deleteRow($id)
    {
        $this->tableGateway->delete(array($this->getPrimaryKeyName() => $id));
    }
} 