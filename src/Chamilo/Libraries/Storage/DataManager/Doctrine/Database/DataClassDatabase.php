<?php
namespace Chamilo\Libraries\Storage\DataManager\Doctrine;

use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger\ExceptionLoggerInterface;
use Chamilo\Libraries\Storage\DataClass\CompositeDataClass;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\DataClass\Property\DataClassProperties;
use Chamilo\Libraries\Storage\DataManager\Doctrine\ResultSet\DataClassResultSet;
use Chamilo\Libraries\Storage\DataManager\Doctrine\ResultSet\RecordResultSet;
use Chamilo\Libraries\Storage\DataManager\Doctrine\Service\ConditionPartTranslatorService;
use Chamilo\Libraries\Storage\DataManager\Interfaces\DataClassDatabaseInterface;
use Chamilo\Libraries\Storage\DataManager\StorageAliasGenerator;
use Chamilo\Libraries\Storage\Exception\DataClassNoResultException;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Parameters\RecordRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\GroupBy;
use Chamilo\Libraries\Storage\Query\Join;
use Chamilo\Libraries\Storage\Query\Joins;
use Chamilo\Libraries\Storage\Query\Variable\ConditionVariable;
use Exception;

/**
 * This class provides basic functionality for database connections Create Table, Get next id, Insert, Update, Delete,
 * Select(with use of conditions), Count(with use of conditions)
 *
 * @package Chamilo\Libraries\Storage\DataManager\Doctrine
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Sven Vanpoucke <sven.vanpoucke@hogent.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class DataClassDatabase implements DataClassDatabaseInterface
{
    use \Chamilo\Libraries\Architecture\Traits\ClassContext;

    // Constants
    const ALIAS_MAX_SORT = 'max_sort';

    /**
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     *
     * @var \Chamilo\Libraries\Storage\DataManager\StorageAliasGenerator
     */
    protected $storageAliasGenerator;

    /**
     *
     * @var \Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger\ExceptionLoggerInterface
     */
    protected $exceptionLogger;

    /**
     *
     * @var \Chamilo\Libraries\Storage\DataManager\Doctrine\Factory\ConditionPartTranslatorFactory
     */
    protected $conditionPartTranslatorFactory;

    /**
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param \Chamilo\Libraries\Storage\DataManager\StorageAliasGenerator $storageAliasGenerator
     * @param \Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger\ExceptionLoggerInterface $exceptionLogger
     * @param \Chamilo\Libraries\Storage\DataManager\Doctrine\Service\ConditionPartTranslatorService $conditionPartTranslatorService
     */
    public function __construct(\Doctrine\DBAL\Connection $connection, StorageAliasGenerator $storageAliasGenerator,
        ExceptionLoggerInterface $exceptionLogger, ConditionPartTranslatorService $conditionPartTranslatorService)
    {
        $this->connection = $connection;
        $this->storageAliasGenerator = $storageAliasGenerator;
        $this->exceptionLogger = $exceptionLogger;
        $this->conditionPartTranslatorService = $conditionPartTranslatorService;
    }

    /**
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     *
     * @return \Chamilo\Libraries\Storage\DataManager\StorageAliasGenerator
     */
    public function getStorageAliasGenerator()
    {
        return $this->storageAliasGenerator;
    }

    /**
     *
     * @param \Chamilo\Libraries\Storage\DataManager\StorageAliasGenerator $storageAliasGenerator
     */
    public function setStorageAliasGenerator($storageAliasGenerator)
    {
        $this->storageAliasGenerator = $storageAliasGenerator;
    }

    /**
     *
     * @return \Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger\ExceptionLoggerInterface
     */
    public function getExceptionLogger()
    {
        return $this->exceptionLogger;
    }

    /**
     *
     * @param \Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger\ExceptionLoggerInterface $exceptionLogger
     */
    public function setExceptionLogger($exceptionLogger)
    {
        $this->exceptionLogger = $exceptionLogger;
    }

    /**
     *
     * @return \Chamilo\Libraries\Storage\DataManager\Doctrine\Service\ConditionPartTranslatorService
     */
    public function getConditionPartTranslatorService()
    {
        return $this->conditionPartTranslatorService;
    }

    /**
     *
     * @param \Chamilo\Libraries\Storage\DataManager\Doctrine\Service\ConditionPartTranslatorService $conditionPartTranslatorService
     */
    public function setConditionPartTranslatorService($conditionPartTranslatorService)
    {
        $this->conditionPartTranslatorService = $conditionPartTranslatorService;
    }

    /**
     *
     * @param \Exception $exception
     */
    public function handleError(\Exception $exception)
    {
        $this->getExceptionLogger()->logException(
            '[Message: ' . $exception->getMessage() . '] [Information: {USER INFO GOES HERE}]');
    }

    /**
     * Escapes a column name in accordance with the database type.
     *
     * @param string $columnName
     * @param string $tableAlias
     * @return string
     */
    public function escapeColumnName($columnName, $tableAlias = null)
    {
        if (! empty($tableAlias))
        {
            return $tableAlias . '.' . $columnName;
        }
        else
        {
            return $columnName;
        }
    }

    /**
     *
     * @param \Chamilo\Libraries\Storage\DataClass\DataClass $object
     * @param boolean $autoAssignIdentifier
     * @return boolean
     */
    public function create(DataClass $dataClass, $autoAssignIdentifier = true)
    {
        if ($dataClass instanceof CompositeDataClass)
        {
            $parentClass = $object::parent_class_name();
            $objectTableName = $parent_class::get_table_name();
        }
        else
        {
            $objectTableName = $dataClass->get_table_name();
        }

        $objectProperties = $dataClass->get_default_properties();

        if ($autoAssignIdentifier && in_array(DataClass::PROPERTY_ID, $dataClass->get_default_property_names()))
        {
            $objectProperties[DataClass::PROPERTY_ID] = null;
        }

        try
        {
            $this->getConnection()->insert($objectTableName, $objectProperties);

            if ($autoAssignIdentifier && in_array(DataClass::PROPERTY_ID, $dataClass->get_default_property_names()))
            {
                $dataClass->setId($this->getConnection()->lastInsertId($objectTableName));
            }

            if ($dataClass instanceof CompositeDataClass && $object::is_extended())
            {
                $objectProperties = $dataClass->get_additional_properties();
                $objectProperties[DataClass::PROPERTY_ID] = $dataClass->getId();

                $this->getConnection()->insert($dataClass->get_table_name(), $objectProperties);
            }

            return true;
        }
        catch (\Exception $exception)
        {
            $this->handleError($exception);
            return false;
        }
    }

    /**
     *
     * @param string $className
     * @param string[] $record
     * @return boolean
     */
    public function createRecord($className, $record)
    {
        try
        {
            $result = $this->getConnection()->insert($className::get_table_name(), $record);
        }
        catch (\Exception $exception)
        {
            $this->handleError($exception);
            return false;
        }

        return true;
    }

    /**
     *
     * @param string $objectTableName
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @param string[] $propertiesToUpdate
     * @throws Exception
     * @return boolean
     */
    public function update($objectTableName, $condition, $propertiesToUpdate)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder->update($objectTableName, $this->getAlias($objectTableName));

        foreach ($propertiesToUpdate as $key => $value)
        {
            $queryBuilder->set($key, $this->escape($value));
        }

        if ($condition instanceof Condition)
        {
            $queryBuilder->where($this->getConditionPartTranslatorService()->translateCondition($this, $condition));
        }
        else
        {
            throw new Exception('Cannot update records without a condition');
        }

        $statement = $this->getConnection()->query($queryBuilder->getSQL());

        if ($statement instanceof \PDOException)
        {
            $this->handleError($statement);
            return false;
        }

        return true;
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\DataClass\Property\DataClassProperties $properties
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @throws Exception
     * @return boolean
     */
    public function updates($class, $properties, $condition)
    {
        if (count($properties->get()) > 0)
        {
            $queryBuilder = $this->getConnection()->createQueryBuilder();
            $queryBuilder->update($class::get_table_name(), $this->get_alias($class::get_table_name()));

            foreach ($properties->get() as $dataClassProperty)
            {
                $queryBuilder->set(
                    $this->getConditionPartTranslatorService()->translateCondition(
                        $this,
                        $dataClassProperty->get_property()),
                    $this->getConditionPartTranslatorService()->translateCondition(
                        $this,
                        $dataClassProperty->get_value()));
            }

            if ($condition)
            {
                $queryBuilder->where($this->getConditionPartTranslatorService()->translateCondition($this, $condition));
            }
            else
            {
                throw new Exception('Cannot update records without a condition');
            }

            $statement = $this->getConnection()->query($queryBuilder->getSQL());

            if (! $statement instanceof \PDOException)
            {
                return true;
            }
            else
            {
                $this->handleError($statement);
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @return boolean
     */
    public function delete($class, $condition)
    {
        $queryBuilder = new QueryBuilder($this->getConnection());
        $queryBuilder->delete($class::get_table_name(), $this->getAlias($class::get_table_name()));
        if (isset($condition))
        {
            $queryBuilder->where($this->getConditionPartTranslatorService()->translateCondition($this, $condition));
        }

        $statement = $this->getConnection()->query($queryBuilder->getSQL());

        if (! $statement instanceof \PDOException)
        {
            return true;
        }
        else
        {
            $this->handleError($statement);
            return false;
        }
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassCountParameters $parameters
     * @return integer
     */
    public function count($class, $parameters)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        if ($parameters->get_property() instanceof ConditionVariable)
        {
            $property = $this->getConditionPartTranslatorService()->translateCondition(
                $this,
                $parameters->get_property());
        }
        else
        {
            $property = '1';
        }

        $queryBuilder->addSelect('COUNT(' . $property . ')');
        $queryBuilder->from($this->prepareTableName($class), $this->getAlias($this->prepareTableName($class)));

        $queryBuilder = $this->processParameters($queryBuilder, $class, $parameters);

        $statement = $this->getConnection()->query($queryBuilder->getSQL());

        if (! $statement instanceof \PDOException)
        {
            $record = $statement->fetch(\PDO::FETCH_NUM);
            return (int) $record[0];
        }
        else
        {
            $this->handleError($statement);
            return false;
        }
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassCountGroupedParameters $parameters
     * @return integer
     */
    public function countGrouped($class, $parameters)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        foreach ($parameters->get_property()->get() as $property)
        {

            $queryBuilder->addSelect($this->getConditionPartTranslatorService()->translateCondition($this, $property));
        }

        $queryBuilder->addSelect('COUNT(1)');
        $queryBuilder->from($class::get_table_name(), $this->getAlias($class::get_table_name()));

        $queryBuilder = $this->processParameters($queryBuilder, $class, $parameters);

        foreach ($parameters->get_property()->get() as $property)
        {
            $queryBuilder->addGroupBy($this->getConditionPartTranslatorService()->translateCondition($this, $property));
        }

        $queryBuilder->having(
            $this->getConditionPartTranslatorService()->translateCondition($this, $parameters->get_having()));
        $statement = $this->getConnection()->query($queryBuilder->getSQL());

        if (! $statement instanceof \PDOException)
        {
            $counts = array();
            while ($record = $statement->fetch(\PDO::FETCH_NUM))
            {
                $counts[$record[0]] = $record[1];
            }

            $record = $statement->fetch(\PDO::FETCH_NUM);

            return $counts;
        }
        else
        {
            $this->handleError($statement);
            return false;
        }
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassParameters $parameters
     */
    protected function processCompositeDataClassJoins($queryBuilder, $class, $parameters)
    {
        if ($parameters->get_joins() instanceof Joins)
        {
            foreach ($parameters->get_joins()->get() as $join)
            {
                if (is_subclass_of($join->get_data_class(), CompositeDataClass::class_name()))
                {
                    if (is_subclass_of($class, $join->get_data_class()))
                    {
                        $join_class = $join->get_data_class();
                        $alias = $this->getAlias($join_class::get_table_name());
                        $queryBuilder->addSelect($alias . '.*');
                    }
                }
            }
        }
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters $parameters
     * @return \Chamilo\Libraries\Storage\DataManager\Doctrine\ResultSet\DataClassResultSet
     */
    public function retrieves($class, DataClassRetrievesParameters $parameters)
    {
        return new DataClassResultSet(
            $this->getRecordsResult($this->buildRetrievesSql($class, $parameters), $class, $parameters),
            $class);
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\RecordRetrievesParameters $parameters
     * @return \Chamilo\Libraries\Storage\DataManager\Doctrine\ResultSet\RecordResultSet
     */
    public function records($class, RecordRetrievesParameters $parameters)
    {
        return new RecordResultSet(
            $this->getRecordsResult($this->buildRecordsSql($class, $parameters), $class, $parameters));
    }

    /**
     *
     * @param string $sql
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassParameters $parameters
     * @throws DataClassNoResultException
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function getRecordsResult($sql, $class, $parameters)
    {
        try
        {
            return $this->getConnection()->query($sql);
        }
        catch (\PDOException $exception)
        {
            $this->handleError($exception);
            throw new DataClassNoResultException($class, $parameters, $sql);
        }
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters $parameters
     * @return string
     */
    public function buildRetrievesSql($class, DataClassRetrievesParameters $parameters)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        $select = $this->getAlias($this->prepareTableName($class)) . '.*';

        if ($parameters->get_distinct())
        {
            $select = 'DISTINCT ' . $select;
        }

        $queryBuilder->addSelect($select);

        $this->processCompositeDataClassJoins($queryBuilder, $class, $parameters);

        return $this->buildBasicRecordsSql($queryBuilder, $class, $parameters);
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\RecordRetrievesParameters $parameters
     * @return string
     */
    public function buildRecordsSql($class, RecordRetrievesParameters $parameters)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        $queryBuilder = $this->processDataClassProperties($queryBuilder, $class, $parameters->get_properties());
        $queryBuilder = $this->processGroupBy($queryBuilder, $parameters->get_group_by());

        return $this->buildBasicRecordsSql($queryBuilder, $class, $parameters);
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $queryBuilder
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassParameters $parameters
     * @return string
     */
    public function buildBasicRecordsSql($queryBuilder, $class, $parameters)
    {
        $queryBuilder->from($this->prepareTableName($class), $this->getAlias($this->prepareTableName($class)));
        $queryBuilder = $this->processParameters($queryBuilder, $class, $parameters);
        $queryBuilder = $this->processOrderBy($queryBuilder, $class, $parameters->get_order_by());
        $queryBuilder = $this->processLimit($queryBuilder, $parameters->get_count(), $parameters->get_offset());
        return $queryBuilder->getSQL();
    }

    /**
     *
     * @param string $class
     * @param string $property
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @return integer
     */
    public function retrieveMaximumValue($class, $property, $condition = null)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder->addSelect(
            'MAX(' . $this->escapeColumnName($property, $this->getAlias($class::get_table_name())) . ') AS ' .
                 self::ALIAS_MAX_SORT);
        $queryBuilder->from($class::get_table_name(), $this->getAlias($class::get_table_name()));

        if (isset($condition))
        {
            $queryBuilder->where(
                $this->getConditionPartTranslatorService()->translateCondition(
                    $this,
                    $condition,
                    $this->getAlias($class::get_table_name())));
        }

        $statement = $this->getConnection()->query($queryBuilder->getSQL());

        if (! $statement instanceof \PDOException)
        {
            $record = $statement->fetch(\PDO::FETCH_NUM);
            return (int) $record[0];
        }
        else
        {
            $this->handleError($statement);
            return false;
        }
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassParameters $parameters
     * @return string[]
     */
    public function retrieve($class, $parameters = null)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder->addSelect($this->getAlias($this->prepareTableName($class)) . '.*');

        $this->processCompositeDataClassJoins($queryBuilder, $class, $parameters);

        return $this->fetchRecord($queryBuilder, $class, $parameters);
    }

    /**
     *
     * @param string $class
     * @return string
     */
    protected function prepareTableName($class)
    {
        if (is_subclass_of($class, CompositeDataClass::class_name()) &&
             get_parent_class($class) == CompositeDataClass::class_name())
        {
            $tableName = $class::get_table_name();
        }
        elseif (is_subclass_of($class, CompositeDataClass::class_name()) && $class::is_extended())
        {
            $tableName = $class::get_table_name();
        }
        elseif (is_subclass_of($class, CompositeDataClass::class_name()) && ! $class::is_extended())
        {
            $parent = $class::parent_class_name();
            $tableName = $parent::get_table_name();
        }
        else
        {
            $tableName = $class::get_table_name();
        }

        return $tableName;
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassParameters $parameters
     * @return string[]
     */
    public function record($class, $parameters = null)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        $groupBy = $parameters->get_group_by();
        if ($groupBy instanceof GroupBy)
        {
            foreach ($groupBy->get_group_by() as $groupByVariable)
            {
                $queryBuilder->addGroupBy(
                    $this->getConditionPartTranslatorService()->translateCondition($this, $groupByVariable));
            }
        }

        if ($parameters->get_properties() instanceof DataClassProperties)
        {

            foreach ($parameters->get_properties()->get() as $conditionVariable)
            {
                $queryBuilder->addSelect(
                    $this->getConditionPartTranslatorService()->translateCondition($this, $conditionVariable));
            }
        }
        else
        {

            return $this->retrieve($class, $parameters);
        }

        return $this->fetchRecord($queryBuilder, $class, $parameters);
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassParameters $parameters
     * @throws DataClassNoResultException
     * @return string[]
     */
    protected function fetchRecord($query_builder, $class, $parameters)
    {
        $query_builder->from($this->prepare_table_name($class), $this->get_alias($this->prepare_table_name($class)));

        $query_builder = $this->process_parameters($query_builder, $class, $parameters);
        $query_builder = $this->process_order_by($query_builder, $class, $parameters->get_order_by());

        $query_builder->setFirstResult(0);
        $query_builder->setMaxResults(1);

        $sqlQuery = $query_builder->getSQL();

        $statement = $this->getConnection()->query($sqlQuery);

        if (! $statement instanceof \PDOException)
        {
            $record = $statement->fetch(\PDO::FETCH_ASSOC);
        }
        else
        {
            $this->handleError($statement);
            throw new DataClassNoResultException($class, $parameters, $sqlQuery);
        }

        if ($record instanceof \PDOException)
        {
            $this->handleError($record);
            throw new DataClassNoResultException($class, $parameters, $sqlQuery);
        }

        if (is_null($record) || ! is_array($record) || empty($record))
        {
            throw new DataClassNoResultException($class, $parameters, $sqlQuery);
        }

        foreach ($record as &$field)
        {
            if (is_resource($field))
            {
                $data = '';
                while (! feof($field))
                    $data .= fread($field, 1024);
                $field = $data;
            }
        }

        return $record;
    }

    /**
     *
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters $parameters
     * @return string[]
     */
    public function distinct($class, DataClassDistinctParameters $parameters)
    {
        $properties = $parameters->get_property();

        if (! is_array($properties))
        {
            $properties = array($properties);
        }

        $select = array();

        foreach ($properties as $property)
        {
            $select[] = $this->escapeColumnName($property, $this->get_alias($class::get_table_name()));
        }

        $query_builder = $this->getConnection()->createQueryBuilder();
        $query_builder->addSelect('DISTINCT ' . implode(',', $select));
        $query_builder->from($class::get_table_name(), $this->get_alias($class::get_table_name()));

        $query_builder = $this->process_parameters($query_builder, $class, $parameters);
        $query_builder = $this->process_order_by($query_builder, $class, $parameters->getOrderBy());

        $statement = $this->getConnection()->query($query_builder->getSQL());

        if (! $statement instanceof \PDOException)
        {
            $distinct_elements = array();
            while ($record = $statement->fetch(\PDO::FETCH_ASSOC))
            {
                if (count($properties) > 1)
                {
                    $distinct_elements[] = $record;
                }
                else
                {
                    $distinct_elements[] = array_pop($record);
                }
            }
            return $distinct_elements;
        }
        else
        {
            $this->handleError($statement);
            return false;
        }
    }

    /**
     *
     * @param string $table_name
     * @return string
     */
    public function getAlias($table_name)
    {
        return $this->getStorageAliasGenerator()->get_table_alias($table_name);
    }

    /**
     *
     * @param string $name
     * @return string
     */
    public function getConstraintName($name)
    {
        $possible_name = '';
        $parts = explode('_', $name);
        foreach ($parts as & $part)
        {
            $possible_name .= $part{0};
        }

        return $possible_name;
    }

    /**
     *
     * @param string $value
     * @param string $type
     * @param boolean $quote
     * @param boolean $escape_wildcards
     *
     * @return string
     */
    public function quote($value, $type = null, $quote = true, $escape_wildcards = false)
    {
        return $this->getConnection()->quote($value, $type, $quote, $escape_wildcards);
    }

    /**
     *
     * @param string $text
     * @param boolean $escape_wildcards
     * @return string
     */
    public function escape($text, $escape_wildcards = false)
    {
        if (! is_null($text))
        {
            return $this->getConnection()->quote($text);
        }
        else
        {
            return 'NULL';
        }
    }

    /**
     * Delegate method for the exec function Exec is used when the query does not expect a resultset but only true /
     * false
     *
     * @param string $query
     * @return boolean
     */
    public function exec($query)
    {
        return $this->getConnection()->exec($query);
    }

    /**
     *
     * @param \Chamilo\Libraries\Storage\DataClass\CompositeDataClass $object
     * @return string[]
     */
    public function retrieveCompositeDataClassAdditionalProperties(CompositeDataClass $object)
    {
        if (! $object->is_extended())
        {
            return array();
        }

        $array = $object->get_additional_property_names();

        if (count($array) == 0)
        {
            $array = array("*");
        }

        $query = 'SELECT ' . implode(',', $array) . ' FROM ' . $object::get_table_name() . ' WHERE ' .
             $object::PROPERTY_ID . '=' . $this->quote($object->get_id());

        $statement = $this->getConnection()->query($query);

        if (! $statement instanceof \PDOException)
        {
            $distinct_elements = array();
            $record = $statement->fetch(\PDO::FETCH_ASSOC);

            $additional_properties = array();

            if (is_array($record))
            {
                $properties = $object->get_additional_property_names();

                if (count($properties) > 0)
                {
                    foreach ($properties as $prop)
                    {
                        if (is_resource($record[$prop]))
                        {
                            $data = '';
                            while (! feof($record[$prop]))
                                $data .= fread($record[$prop], 1024);
                            $record[$prop] = $data;
                        }
                        $additional_properties[$prop] = $record[$prop];
                    }
                }
            }
            return $additional_properties;
        }
        else
        {
            $this->handleError($statement);
            return false;
        }
    }

    /**
     *
     * @param unknown $function
     * @throws Exception
     * @return mixed
     */
    public function transactional($function)
    {
        // Rather than directly using Doctrine's version of transactional, we implement
        // an intermediate function that throws an exception if the function returns #f.
        // This mediates between Chamilo's convention of returning #f to signal failure
        // versus Doctrine's use of Exceptions.
        $throw_on_false = function ($connection) use ($function)
        {
            $result = call_user_func($function, $connection);
            if (! $result)
            {
                throw new Exception();
            }
            else
            {
                return $result;
            }
        };

        try
        {
            $this->getConnection()->transactional($throw_on_false);
            return true;
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Query\OrderBy[] $order_by
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function processOrderBy($query_builder, $class, $order_by)
    {
        if (is_null($order_by))
        {
            $order_by = array();
        }
        elseif (! is_array($order_by))
        {
            $order_by = array($order_by);
        }

        foreach ($order_by as $order)
        {
            $query_builder->addOrderBy(
                $this->getConditionPartTranslatorService()->translateCondition($this, $order->get_property()),
                ($order->get_direction() == SORT_DESC ? 'DESC' : 'ASC'));
        }

        return $query_builder;
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param int $count
     * @param int $offset
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function processLimit($query_builder, $count = null, $offset = null)
    {
        if (intval($count) > 0)
        {
            $query_builder->setMaxResults(intval($count));
        }

        if (intval($offset) > 0)
        {
            $query_builder->setFirstResult(intval($offset));
        }

        return $query_builder;
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param string $class
     * @param \Chamilo\Libraries\Storage\DataClass\Property\DataClassProperties $properties
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function processDataClassProperties($query_builder, $class, $properties)
    {
        if ($properties instanceof DataClassProperties)
        {
            foreach ($properties->get() as $condition_variable)
            {
                $query_builder->addSelect(
                    $this->getConditionPartTranslatorService()->translateCondition($this, $condition_variable));
            }
        }
        else
        {
            $query_builder->addSelect($this->get_alias($class::get_table_name()) . '.*');
        }

        return $query_builder;
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param \Chamilo\Libraries\Storage\Query\GroupBy $group_by
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function processGroupBy($query_builder, $group_by)
    {
        if ($group_by instanceof GroupBy)
        {
            foreach ($group_by->get() as $group_by_variable)
            {
                $query_builder->addGroupBy(
                    $this->getConditionPartTranslatorService()->translateCondition($this, $group_by_variable));
            }
        }

        return $query_builder;
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Query\Joins $joins
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function processJoins($query_builder, $class, $joins)
    {
        if ($joins instanceof Joins)
        {
            foreach ($joins->get() as $join)
            {
                $join_condition = $this->getConditionPartTranslatorService()->translateCondition(
                    $this,
                    $join->get_condition());
                $data_class_name = $join->get_data_class();

                switch ($join->get_type())
                {
                    case Join::TYPE_NORMAL :
                        $query_builder->join(
                            $this->get_alias($class::get_table_name()),
                            $data_class_name::get_table_name(),
                            $this->get_alias($data_class_name::get_table_name()),
                            $join_condition);
                        break;
                    case Join::TYPE_RIGHT :
                        $query_builder->rightJoin(
                            $this->get_alias($class::get_table_name()),
                            $data_class_name::get_table_name(),
                            $this->get_alias($data_class_name::get_table_name()),
                            $join_condition);
                        break;
                    case Join::TYPE_LEFT :
                        $query_builder->leftJoin(
                            $this->get_alias($class::get_table_name()),
                            $data_class_name::get_table_name(),
                            $this->get_alias($data_class_name::get_table_name()),
                            $join_condition);
                        break;
                }
            }
        }
        return $query_builder;
    }

    /**
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function processCondition($query_builder, $class, $condition)
    {
        if ($condition instanceof Condition)
        {
            $query_builder->where(
                $this->getConditionPartTranslatorService()->translateCondition(
                    $this,
                    $condition,
                    $this->get_alias($this->prepare_table_name($class))));
        }

        return $query_builder;
    }

    /**
     *
     * @param Condition $condition
     * @return string
     */
    public function translateCondition(Condition $condition = null)
    {
        return $this->getConditionPartTranslatorService()->translateCondition($this, $condition);
    }

    /**
     * Processes the parameters
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query_builder
     * @param string $class
     * @param \Chamilo\Libraries\Storage\Parameters\DataClassParameters $parameters
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function processParameters($query_builder, $class, $parameters)
    {
        $query_builder = $this->process_joins($query_builder, $class, $parameters->get_joins());
        $query_builder = $this->process_condition($query_builder, $class, $parameters->get_condition());
        return $query_builder;
    }

    /**
     *
     * @return string
     */
    public static function package()
    {
        return ClassnameUtilities::getInstance()->getNamespaceParent(static::context(), 3);
    }
}
