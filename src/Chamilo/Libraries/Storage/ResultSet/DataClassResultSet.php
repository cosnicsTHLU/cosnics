<?php
namespace Chamilo\Libraries\Storage\ResultSet;

use Chamilo\Libraries\Storage\Cache\DataClassCache;
use Chamilo\Libraries\Storage\Cache\DataClassResultCache;
use Chamilo\Libraries\Storage\DataClass\CompositeDataClass;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 *
 * @package Chamilo\Libraries\Storage\ResultSet
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class DataClassResultSet extends ArrayResultSet
{

    /**
     * The DataClass class name
     *
     * @var string
     */
    private $class_name;

    /**
     * Constructor
     *
     * @param string $class_name
     * @param \Chamilo\Libraries\Storage\DataClass\DataClass[]
     */
    public function __construct($class_name, $objects)
    {
        parent :: __construct($objects);

        $this->class_name = $class_name;
    }

    /**
     * Get the DataClass class name
     *
     * @return string
     */
    public function get_class_name()
    {
        return $this->class_name;
    }

    /**
     * Convert the record to a DataClass object
     *
     * @param string $class_name
     * @param string[] $record
     * @return \Chamilo\Libraries\Storage\DataClass\DataClass
     */
    public function get_object($class_name, $record)
    {
        $cached = false;

        foreach ($class_name :: get_cacheable_property_names() as $cacheable_property)
        {
            $value = $record[$cacheable_property];
            if (isset($value) && ! is_null($value))
            {
                $cacheable_property_parameters = new DataClassRetrieveParameters(
                    new EqualityCondition(
                        new PropertyConditionVariable($class_name, $cacheable_property),
                        new StaticConditionVariable($value)));

                if (DataClassCache :: exists($class_name, $cacheable_property_parameters))
                {
                    $object = DataClassResultCache :: get($class_name, $cacheable_property_parameters);
                    $cached = true;
                    break;
                }
            }
        }

        if (! $cached)
        {
            $base = (is_subclass_of($class_name, CompositeDataClass :: class_name()) ? CompositeDataClass :: class_name() : DataClass :: class_name());
            $class_name = (is_subclass_of($class_name, CompositeDataClass :: class_name()) ? $record[CompositeDataClass :: PROPERTY_TYPE] : $class_name);
            $object = $base :: factory($class_name, $record);

            DataClassResultCache :: add($object);
        }

        return $object;
    }
}
