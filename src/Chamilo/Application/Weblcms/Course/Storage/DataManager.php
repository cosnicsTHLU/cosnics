<?php
namespace Chamilo\Application\Weblcms\Course\Storage;

use Chamilo\Application\Weblcms\Course\Storage\DataClass\Course;
use Chamilo\Application\Weblcms\Course\Storage\DataClass\CourseRelCourseSetting;
use Chamilo\Application\Weblcms\Course\Storage\DataManager\Implementation\DoctrineExtension;
use Chamilo\Application\Weblcms\CourseSettingsController;
use Chamilo\Application\Weblcms\CourseType\Storage\DataClass\CourseType;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublicationCategory;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseSection;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseSetting;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseTool;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseTypeUserCategoryRelCourse;
use Chamilo\Core\Group\Storage\DataClass\Group;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Storage\DataClass\Property\DataClassProperties;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Parameters\RecordRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Condition\InequalityCondition;
use Chamilo\Libraries\Storage\Query\Condition\NotCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Condition\SubselectCondition;
use Chamilo\Libraries\Storage\Query\GroupBy;
use Chamilo\Libraries\Storage\Query\Join;
use Chamilo\Libraries\Storage\Query\Joins;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\Variable\FixedPropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\PropertiesConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Storage\ResultSet\EmptyResultSet;
use Chamilo\Libraries\Storage\ResultSet\RecordResultSet;
use Chamilo\Libraries\Storage\ResultSet\ResultSet;

/**
 * This class represents the data manager for this package
 * 
 * @author Sven Vanpoucke - Hogeschool Gent - Refactoring from MDB2
 * @package application.weblcms.course
 */
class DataManager extends \Chamilo\Application\Weblcms\Storage\DataManager
{
    const PREFIX = 'weblcms_';

    /**
     * Caching variable to check if a user is subscribed
     * 
     * @var bool
     */
    private static $is_subscribed_cache;

    /**
     * **************************************************************************************************************
     * Course Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Retrieves all the courses from a given user
     * 
     * @param User $user
     * @param Condition $condition
     * @param int $offset
     * @param int $max_objects
     * @param array $order_by
     *
     * @return \libraries\storage\ResultSet<Course>
     */
    public static function retrieve_all_courses_from_user(User $user, Condition $condition = null, $offset = 0, 
        $max_objects = - 1, $order_by = null)
    {
        return self::retrieve_user_courses($user, $condition, $offset, $max_objects, $order_by);
    }

    /**
     * Retrieves the courses from a user where a user is a student
     * 
     * @param User $user
     * @param Condition $condition
     * @param int $offset
     * @param int $max_objects
     * @param array $order_by
     *
     * @return \libraries\storage\ResultSet
     */
    public static function retrieve_courses_from_user_where_user_is_student(User $user, Condition $condition = null, 
        $offset = 0, $max_objects = - 1, $order_by = null)
    {
        return self::retrieve_user_courses(
            $user, 
            $condition, 
            $offset, 
            $max_objects, 
            $order_by, 
            CourseEntityRelation::STATUS_STUDENT);
    }

    /**
     * Retrieves the courses from a given user where the user is a teacher
     * 
     * @param User $user
     * @param \libraries\storage\Condition $condition
     * @param int $offset
     * @param int $max_objects
     * @param array $order_by
     *
     * @return \libraries\storage\ResultSet
     */
    public static function retrieve_courses_from_user_where_user_is_teacher(User $user, Condition $condition = null, 
        $offset = 0, $max_objects = - 1, $order_by = null)
    {
        return self::retrieve_user_courses(
            $user, 
            $condition, 
            $offset, 
            $max_objects, 
            $order_by, 
            CourseEntityRelation::STATUS_TEACHER);
    }

    /**
     * Counts all the courses from a given user
     * 
     * @param User $user
     * @param Condition $condition
     *
     * @return \libraries\storage\ResultSet<Course>
     */
    public static function count_all_courses_from_user(User $user, Condition $condition = null)
    {
        return self::count_user_courses($user, $condition);
    }

    /**
     * Counts the courses from a user where a user is a student
     * 
     * @param User $user
     * @param Condition $condition
     *
     * @return \libraries\storage\ResultSet
     */
    public static function count_courses_from_user_where_user_is_student(User $user, Condition $condition = null)
    {
        return self::count_user_courses($user, $condition, CourseEntityRelation::STATUS_STUDENT);
    }

    /**
     * Counts the courses from a given user where the user is a teacher
     * 
     * @param User $user
     * @param Condtion $condition
     *
     * @return \libraries\storage\ResultSet
     */
    public static function count_courses_from_user_where_user_is_teacher(User $user, Condition $condition = null)
    {
        return self::count_user_courses($user, $condition, CourseEntityRelation::STATUS_TEACHER);
    }

    /**
     * Retrieves a list of user courses joined with course type, optionally limiting the result by the courses by the
     * status of a user in that course
     * 
     * @param \core\user\storage\data_class\User $user
     * @param Condition $condition
     * @param int $offset
     * @param int $max_objects
     * @param ObjectTableOrder[] $order_by
     * @param int $user_status
     *
     * @return \libraries\storage\RecordResultSet
     */
    public static function retrieve_users_courses_with_course_type(User $user, Condition $condition = null, $offset = null, 
        $max_objects = null, $order_by = null, $user_status = null)
    {
        $course_type_joins = self::get_course_with_course_type_joins();
        
        $group_by = new GroupBy();
        $group_by->add(new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_ID));
        
        $parameters = new RecordRetrievesParameters(
            self::get_courses_with_course_type_properties(), 
            self::get_user_courses_condition($user, $condition, $user_status), 
            $max_objects, 
            $offset, 
            $order_by, 
            $course_type_joins, 
            $group_by);
        
        return self::records(Course::class_name(), $parameters);
    }

    /**
     * Retrieves a list of available courses
     * 
     * @param $condition \libraries\storage\Condition
     * @param $offset int
     * @param $max_objects int
     * @param $order_by \libraries\ObjectTableOrder
     *
     * @return \libraries\storage\RecordResultSet
     */
    public static function retrieve_courses_with_course_type($condition = null, $offset = null, $max_objects = null, 
        $order_by = null)
    {
        $parameters = new RecordRetrievesParameters(
            self::get_courses_with_course_type_properties(), 
            $condition, 
            $max_objects, 
            $offset, 
            $order_by, 
            self::get_course_with_course_type_joins());
        
        return self::records(Course::class_name(), $parameters);
    }

    /**
     * Counts the courses
     * 
     * @param $condition \libraries\storage\Condition
     *
     * @return int
     */
    public static function count_courses_with_course_type($condition = null)
    {
        $parameters = new DataClassCountParameters($condition, self::get_course_with_course_type_joins());
        
        return self::count(Course::class_name(), $parameters);
    }

    /**
     * Updates the courses from a given course type with the given properties
     * 
     * @param int $course_type_id
     * @param DataClassProperties $properties
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function update_courses_from_course_type_with_properties($course_type_id = 0, 
        DataClassProperties $properties = null)
    {
        if (! $properties instanceof DataClassProperties || ! count($properties->get()) > 0)
        {
            throw new \Exception('No valid properties selected to update the courses from a course type');
        }
        
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_COURSE_TYPE_ID), 
            new StaticConditionVariable($course_type_id));
        
        return self::updates(Course::class_name(), $properties, $condition);
    }

    /**
     * Counts the courses for a given course type
     * 
     * @param int $course_type_id - [OPTIONAL] default: 0
     * @return int
     */
    public static function count_courses_from_course_type($course_type_id = 0)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_COURSE_TYPE_ID), 
            new StaticConditionVariable($course_type_id));
        
        return self::count(Course::class_name(), $condition);
    }

    /**
     * Deletes courses by a given course type id
     * 
     * @param int $course_type_id
     *
     * @return bool
     */
    public static function delete_courses_by_course_type_id($course_type_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_COURSE_TYPE_ID), 
            new StaticConditionVariable($course_type_id));
        
        $courses = self::retrieves(Course::class_name(), new DataClassRetrievesParameters($condition));
        while ($course = $courses->next_result())
        {
            if (! $course->delete())
            {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Checks if a given visual code is used or not
     * 
     * @param string $visual_code
     * @param int $id - The object to exclude
     * @return bool
     */
    public static function is_visual_code_available($visual_code, $id = null)
    {
        $conditions = array();
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_VISUAL_CODE), 
            new StaticConditionVariable($visual_code));
        
        if ($id)
        {
            $conditions[] = new NotCondition(
                new EqualityCondition(
                    new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_ID), 
                    new StaticConditionVariable($id)));
        }
        
        $condition = new AndCondition($conditions);
        
        return self::count(Course::class_name(), $condition) == 0;
    }

    public static function retrieve_course($id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_ID), 
            new StaticConditionVariable($id));
        
        return self::retrieve(Course::class_name(), new DataClassRetrieveParameters($condition));
    }

    /**
     * Retrieves a course by a given visual code
     * 
     * @param string $visual_code
     *
     * @return Course
     */
    public static function retrieve_course_by_visual_code($visual_code)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_VISUAL_CODE), 
            new StaticConditionVariable($visual_code));
        
        return self::retrieve(Course::class_name(), new DataClassRetrieveParameters($condition));
    }

    /**
     * Counts the content object publications of a certain course
     * 
     * @param int $course_id
     *
     * @return int
     */
    public static function count_course_content_object_publications($course_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class_name(), 
                ContentObjectPublication::PROPERTY_COURSE_ID), 
            new StaticConditionVariable($course_id));
        
        return \Chamilo\Application\Weblcms\Storage\DataManager::count_content_object_publications($condition);
    }

    /**
     * Retrieves ContentObjectPublications of a certain course
     * 
     * @param int $course_id
     * @param array $order_by
     *
     * @return \libraries\storage\ResultSet
     */
    public static function retrieve_content_object_publications_from_course($course_id, $order_by = array())
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class_name(), 
                ContentObjectPublication::PROPERTY_COURSE_ID), 
            new StaticConditionVariable($course_id));
        
        return \Chamilo\Application\Weblcms\Course\Storage\DataManager::retrieve_content_object_publications(
            $condition, 
            $order_by);
    }

    /**
     * Retrieves the ContentObjectPublicationCategories of a certain course
     * 
     * @param int $course_id
     * @param array $tools
     * @param array $order_by
     *
     * @return \libraries\storage\ResultSet
     */
    public static function retrieve_content_object_publication_categories_from_course($course_id, $tools = array(), 
        $order_by = array())
    {
        $conditions = array();
        
        if (count($tools) > 0)
        {
            $conditions[] = new InCondition(
                new PropertyConditionVariable(
                    ContentObjectPublicationCategory::class_name(), 
                    ContentObjectPublicationCategory::PROPERTY_TOOL), 
                $tools);
        }
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class_name(), 
                ContentObjectPublicationCategory::PROPERTY_COURSE), 
            new StaticConditionVariable($course_id));
        
        $condition = new AndCondition($conditions);
        
        $parameters = new DataClassRetrievesParameters($condition, null, null, $order_by);
        
        return self::retrieves(ContentObjectPublicationCategory::class_name(), $parameters);
    }

    /**
     * **************************************************************************************************************
     * Course Helper Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Counts the courses joined with the course user relation and course group relation, optionally limiting the result
     * by the courses by the status of user in that course
     * 
     * @param \core\user\storage\data_class\User $user
     * @param \libraries\storage\Condition $condition
     * @param int $user_status
     *
     * @return int
     */
    public static function count_user_courses(User $user, Condition $condition = null, $user_status = null)
    {
        $parameters = new DataClassCountParameters(self::get_user_courses_condition($user, $condition, $user_status));
        
        return self::count(Course::class_name(), $parameters);
    }

    /**
     * Retrieves the courses joined with the course user relation and course group relation, optionally limiting the
     * result by the courses by the status of a user in that course
     * 
     * @param \core\user\storage\data_class\User $user
     * @param \libraries\storage\Condition $condition
     * @param int $offset
     * @param int $max_objects
     * @param \libraries\ObjectTableOrder[] $order_by
     * @param int $user_status
     *
     * @return \libraries\storage\ResultSet<Course>
     */
    protected static function retrieve_user_courses(User $user, Condition $condition = null, $offset = 0, $max_objects = - 1, 
        $order_by = null, $user_status = null)
    {
        $parameters = new DataClassRetrievesParameters(
            self::get_user_courses_condition($user, $condition, $user_status), 
            $max_objects, 
            $offset, 
            $order_by);
        
        return self::retrieves(Course::class_name(), $parameters);
    }

    /**
     * Returns the conditions to retrieve the courses of a given user, optionally limited by the courses by the status
     * of the user in that course
     * 
     * @param User $user
     * @param Condition $condition
     * @param int $user_status
     *
     * @return Condition
     */
    protected static function get_user_courses_condition(User $user, $condition, $user_status = null)
    {
        $conditions = array();
        
        $course_ids = self::getSubscribedCourseIdentifiersByRelation($user, $user_status);
        
        $conditions[] = new InCondition(
            new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_ID), 
            $course_ids);
        
        if ($condition)
        {
            $conditions[] = $condition;
        }
        
        return new AndCondition($conditions);
    }

    protected static function getSubscribedCourseIdentifiersByRelation($user, $userStatus = null)
    {
        $conditions = array();
        
        $entityConditions = array();
        
        $userConditions = array();
        $userConditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::class_name(), 
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::PROPERTY_ENTITY_ID), 
            new StaticConditionVariable($user->getId()));
        $userConditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::class_name(), 
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::ENTITY_TYPE_USER));
        
        $entityConditions[] = new AndCondition($userConditions);
        
        $groupConditions = array();
        $groupConditions[] = new InCondition(
            new PropertyConditionVariable(
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::class_name(), 
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::PROPERTY_ENTITY_ID), 
            $user->get_groups(true));
        $groupConditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::class_name(), 
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(
                \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::ENTITY_TYPE_GROUP));
        
        $entityConditions[] = new AndCondition($groupConditions);
        
        $conditions[] = new OrCondition($entityConditions);
        
        if ($userStatus)
        {
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::class_name(), 
                    \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::PROPERTY_STATUS), 
                new StaticConditionVariable($userStatus));
        }
        
        $parameters = new DataClassDistinctParameters(
            new AndCondition($conditions), 
            \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::PROPERTY_COURSE_ID);
        
        return self::distinct(
            \Chamilo\Application\Weblcms\Storage\DataClass\CourseEntityRelation::class_name(), 
            $parameters);
    }

    /**
     * Returns the properties for the courses with course type
     * 
     * @return \libraries\storage\DataClassProperties
     */
    protected static function get_courses_with_course_type_properties()
    {
        $properties = new DataClassProperties();
        
        $properties->add(new PropertiesConditionVariable(Course::class_name()));
        $properties->add(
            new FixedPropertyConditionVariable(
                CourseType::class_name(), 
                CourseType::PROPERTY_TITLE, 
                Course::PROPERTY_COURSE_TYPE_TITLE));
        
        return $properties;
    }

    /**
     * Returns the joins for the courses with the course types
     * 
     * @return Joins
     */
    protected static function get_course_with_course_type_joins()
    {
        $joins = new Joins();
        
        $joins->add(
            new Join(
                CourseType::class_name(), 
                new EqualityCondition(
                    new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_COURSE_TYPE_ID), 
                    new PropertyConditionVariable(CourseType::class_name(), CourseType::PROPERTY_ID)), 
                Join::TYPE_LEFT));
        
        return $joins;
    }

    /**
     * **************************************************************************************************************
     * CourseEntityRelation Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Checks whether or not the given user is teacher of the given course by direct subscription
     * 
     * @param int $course_id
     * @param int $user_id
     *
     * @return bool
     */
    public static function is_teacher_by_direct_subscription($course_id, $user_id)
    {
        $conditions = array();
        
        $conditions[] = self::get_course_user_relation_by_course_and_user_condition($course_id, $user_id);
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_STATUS), 
            new StaticConditionVariable(CourseEntityRelation::STATUS_TEACHER));
        
        $condition = new AndCondition($conditions);
        
        return self::count(CourseEntityRelation::class_name(), new DataClassCountParameters($condition)) > 0;
    }

    /**
     * Retrieves all the users that are directly subscribed to the course
     * 
     * @param \libraries\storage\Condition $condition
     * @param int $offset
     * @param int $count
     * @param \libraries\ObjectTableOrder $order_property
     *
     * @return \libraries\storage\RecordResultSet
     */
    public static function retrieve_users_directly_subscribed_to_course($condition = null, $offset = null, $count = null, 
        $order_property = null)
    {
        $entityTypeCondition = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_USER));
        
        if ($condition instanceof Condition)
        {
            $conditions = array();
            $conditions[] = $condition;
            $conditions[] = $entityTypeCondition;
            
            $condition = new AndCondition($conditions);
        }
        else
        {
            $condition = $entityTypeCondition;
        }
        
        $properties = new DataClassProperties();
        
        $properties->add(new PropertyConditionVariable(User::class_name(), User::PROPERTY_ID));
        $properties->add(new PropertyConditionVariable(User::class_name(), User::PROPERTY_OFFICIAL_CODE));
        $properties->add(new PropertyConditionVariable(User::class_name(), User::PROPERTY_LASTNAME));
        $properties->add(new PropertyConditionVariable(User::class_name(), User::PROPERTY_FIRSTNAME));
        $properties->add(new PropertyConditionVariable(User::class_name(), User::PROPERTY_USERNAME));
        $properties->add(new PropertyConditionVariable(User::class_name(), User::PROPERTY_EMAIL));
        
        $properties->add(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_STATUS));
        
        $joins = self::get_course_rel_user_joins();
        $parameters = new RecordRetrievesParameters($properties, $condition, $count, $offset, $order_property, $joins);
        
        return self::records(CourseEntityRelation::class_name(), $parameters);
    }

    /**
     * Retrieves the users directly subscribed to course by a given course id
     * 
     * @param int $course_id
     *
     * @return \libraries\storage\RecordResultSet
     */
    public static function retrieve_users_directly_subscribed_to_course_by_id($course_id)
    {
        return self::retrieve_users_directly_subscribed_to_course(
            new EqualityCondition(
                new PropertyConditionVariable(
                    CourseEntityRelation::class_name(), 
                    CourseEntityRelation::PROPERTY_COURSE_ID), 
                new StaticConditionVariable($course_id)));
    }

    /**
     * Counts all users directly subscribed to a course, users that are subscribed through groups are not counted.
     * 
     * @param $condition Condition
     *
     * @return int
     */
    public static function count_users_directly_subscribed_to_course($condition = null)
    {
        $entityTypeCondition = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_USER));
        
        if ($condition instanceof Condition)
        {
            $conditions = array();
            $conditions[] = $condition;
            $conditions[] = $entityTypeCondition;
            
            $condition = new AndCondition($conditions);
        }
        else
        {
            $condition = $entityTypeCondition;
        }
        
        $parameters = new DataClassCountParameters($condition, self::get_course_rel_user_joins());
        
        return self::count(CourseEntityRelation::class_name(), $parameters);
    }

    /**
     * Retrieves all users not directly subscribed to a course, users that are subscribed through groups are still shown
     * in this list.
     * 
     * @param int $course_id
     * @param $condition Condition
     * @param $offset int
     * @param $count int
     * @param $order_property order
     *
     * @return \libraries\storage\ResultSet
     */
    public static function retrieve_users_not_subscribed_to_course($course_id, $condition = null, $offset = null, $count = null, 
        $order_property = null)
    {
        $parameters = new DataClassRetrievesParameters(
            self::get_users_not_subscribed_to_course_condition($course_id, $condition), 
            $count, 
            $offset, 
            $order_property);
        
        return \Chamilo\Core\User\Storage\DataManager::retrieves(
            \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
            $parameters);
    }

    /**
     * Counts all users not directly subscribed to a course, users that are subscribed through groups are still counted.
     * 
     * @param int $course_id
     * @param $condition Condition
     *
     * @return int
     */
    public static function count_users_not_subscribed_to_course($course_id, $condition = null)
    {
        $parameters = new DataClassCountParameters(
            self::get_users_not_subscribed_to_course_condition($course_id, $condition));
        
        return \Chamilo\Core\User\Storage\DataManager::count(
            \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
            $parameters);
    }

    /**
     * Deletes the course user relations for given user and course(s)
     * 
     * @param $user_id int
     * @param $course_ids int[] - [OPTIONAL] default empty array
     * @return boolean
     */
    public static function delete_course_user_relations_for_user_and_courses($user_id, $course_ids = array())
    {
        $conditions = array();
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_ID), 
            new StaticConditionVariable($user_id));
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_USER));
        
        $course_ids = (array) $course_ids;
        
        if (count($course_ids) > 0)
        {
            $conditions[] = new InCondition(
                new PropertyConditionVariable(
                    CourseEntityRelation::class_name(), 
                    CourseEntityRelation::PROPERTY_COURSE_ID), 
                $course_ids);
        }
        
        $condition = new AndCondition($conditions);
        
        return self::deletes(CourseEntityRelation::class_name(), $condition);
    }

    /**
     * Returns whether or not a user is directly subscribed to a course
     * 
     * @param $user_id int
     * @param $course_id int
     *
     * @return boolean
     */
    public static function is_user_direct_subscribed_to_course($user_id, $course_id)
    {
        return self::count(
            CourseEntityRelation::class_name(), 
            self::get_course_user_relation_by_course_and_user_condition($course_id, $user_id)) > 0;
    }

    /**
     * Returns whether or not a group is directly subscribed to a course
     * 
     * @param integer $course_id
     * @param int $group_id
     *
     * @return boolean
     */
    public static function is_group_direct_subscribed_to_course($course_id, $group_id)
    {
        return self::count(
            CourseEntityRelation::class_name(), 
            self::get_course_group_relation_by_course_and_group_condition($course_id, $group_id)) > 0;
    }

    /**
     * Retrieves a course user relation by course_id and user_id
     * 
     * @param int $course_id
     * @param int $user_id
     *
     * @return CourseEntityRelation
     */
    public static function retrieve_course_user_relation_by_course_and_user($course_id, $user_id)
    {
        return self::retrieve(
            CourseEntityRelation::class_name(), 
            new DataClassRetrieveParameters(
                self::get_course_user_relation_by_course_and_user_condition($course_id, $user_id)));
    }

    /**
     * Subscribes a user to a course
     * 
     * @param int $course_id
     * @param int $status
     * @param int $user_id
     *
     * @return bool
     */
    public static function subscribe_user_to_course($course_id, $status, $user_id)
    {
        $course_user_relation = new CourseEntityRelation();
        
        $course_user_relation->set_course_id($course_id);
        $course_user_relation->setEntityId($user_id);
        $course_user_relation->setEntityType(CourseEntityRelation::ENTITY_TYPE_USER);
        $course_user_relation->set_status($status);
        
        return $course_user_relation->create();
    }

    /**
     * Unsubscribes a user from a course
     * 
     * @param int $course_id
     * @param int $user_id
     *
     * @return mixed
     */
    public static function unsubscribe_user_from_course($course_id, $user_id)
    {
        return self::deletes(
            CourseEntityRelation::class_name(), 
            self::get_course_user_relation_by_course_and_user_condition($course_id, $user_id));
    }

    /**
     * Retrieves all users that are admins in the given course.
     * 
     * @param $course_id int The id of the course
     * @return RecordResultSet
     */
    public static function retrieve_teachers_directly_subscribed_to_course($course_id)
    {
        return self::retrieve_users_directly_subscribed_to_course_by_status(
            $course_id, 
            CourseEntityRelation::STATUS_TEACHER);
    }

    /**
     * Retrieves all users that are students in the given course.
     * 
     * @param $course_id int The id of the course
     * @return RecordResultSet
     */
    public static function retrieve_students_directly_subscribed_to_course($course_id)
    {
        return self::retrieve_users_directly_subscribed_to_course_by_status(
            $course_id, 
            CourseEntityRelation::STATUS_STUDENT);
    }

    /**
     * Retrieves all users in the given course with a given status.
     * 
     * @param $course_id int The id of the course
     * @param int $status
     *
     * @return RecordResultSet
     */
    public static function retrieve_users_directly_subscribed_to_course_by_status($course_id, 
        $status = CourseEntityRelation::STATUS_STUDENT)
    {
        $conditions = array();
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_COURSE_ID), 
            new StaticConditionVariable($course_id));
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_STATUS), 
            new StaticConditionVariable($status));
        
        return self::retrieve_users_directly_subscribed_to_course(new AndCondition($conditions));
    }

    /**
     * **************************************************************************************************************
     * CourseEntityRelation Helper Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Returns the joins for the user that are subscribed to a course
     * 
     * @return Joins
     */
    protected static function get_course_rel_user_joins()
    {
        $joinConditions = array();
        $joinConditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_ID), 
            new PropertyConditionVariable(User::class_name(), User::PROPERTY_ID));
        $joinConditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_USER));
        
        $joins = new Joins();
        $joins->add(new Join(User::class_name(), new AndCondition($joinConditions)));
        
        return $joins;
    }

    /**
     * Returns the condition for the users not subscribed functionality
     * 
     * @param $course_id
     * @param Condition $condition
     *
     * @return Condition
     */
    protected static function get_users_not_subscribed_to_course_condition($course_id, Condition $condition)
    {
        $courseConditions = array();
        $courseConditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_COURSE_ID), 
            new StaticConditionVariable($course_id));
        $courseConditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_USER));
        
        $parameters = new DataClassDistinctParameters(
            new AndCondition($courseConditions), 
            CourseEntityRelation::PROPERTY_ENTITY_ID);
        $user_ids = self::distinct(CourseEntityRelation::class_name(), $parameters);
        
        $conditions = array();
        
        $conditions[] = new NotCondition(
            new InCondition(
                new PropertyConditionVariable(
                    \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
                    \Chamilo\Core\User\Storage\DataClass\User::PROPERTY_ID), 
                $user_ids));
        
        if ($condition)
        {
            $conditions[] = $condition;
        }
        
        return new AndCondition($conditions);
    }

    /**
     * Returns the condition for the get course user relation by course and user
     * 
     * @param int $course_id
     * @param int $user_id
     *
     * @return Condition
     */
    protected static function get_course_user_relation_by_course_and_user_condition($course_id, $user_id)
    {
        $conditions = array();
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_ID), 
            new StaticConditionVariable($user_id));
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_USER));
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_COURSE_ID), 
            new StaticConditionVariable($course_id));
        
        return new AndCondition($conditions);
    }

    /**
     * **************************************************************************************************************
     * CourseEntityRelation Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Cache to check if a user is teacher through platform groups
     * 
     * @var bool[int][int]
     */
    private static $is_teacher_cache;

    /**
     * Checks whether or not the given user is teacher of the given course by platform_group subscription
     * 
     * @param int $course_id
     * @param User $user
     *
     * @return boolean
     */
    public static function is_teacher_by_platform_group_subscription($course_id, User $user)
    {
        if (is_null(self::$is_teacher_cache[$course_id][$user->get_id()]))
        {
            $group_ids = $user->get_groups(true);
            
            $conditions = array();
            
            if (count($group_ids) == 0)
            {
                self::$is_teacher_cache[$course_id][$user->get_id()] = false;
            }
            else
            {
                $conditions[] = new InCondition(
                    new PropertyConditionVariable(
                        CourseEntityRelation::class_name(), 
                        CourseEntityRelation::PROPERTY_ENTITY_ID), 
                    $group_ids);
                
                $conditions[] = new InCondition(
                    new PropertyConditionVariable(
                        CourseEntityRelation::class_name(), 
                        CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
                    CourseEntityRelation::ENTITY_TYPE_GROUP);
                
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        CourseEntityRelation::class_name(), 
                        CourseEntityRelation::PROPERTY_STATUS), 
                    new StaticConditionVariable(CourseEntityRelation::STATUS_TEACHER));
                
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        CourseEntityRelation::class_name(), 
                        CourseEntityRelation::PROPERTY_COURSE_ID), 
                    new StaticConditionVariable($course_id));
                
                $condition = new AndCondition($conditions);
                
                self::$is_teacher_cache[$course_id][$user->get_id()] = DataManager::count(
                    CourseEntityRelation::class_name(), 
                    $condition) > 0;
            }
        }
        
        return self::$is_teacher_cache[$course_id][$user->get_id()];
    }

    /**
     * Retrieves the groups that are directly subscribed to a course
     * 
     * @param Condition $condition
     * @param int $offset
     * @param int $count
     * @param ObjectTableOrder[] $order_property
     *
     * @return bool
     */
    public static function retrieve_groups_directly_subscribed_to_course($condition = null, $offset = null, $count = null, 
        $order_property = null)
    {
        $entityTypeCondition = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_GROUP));
        
        if ($condition instanceof Condition)
        {
            $conditions = array();
            $conditions[] = $condition;
            $conditions[] = $entityTypeCondition;
            
            $condition = new AndCondition($conditions);
        }
        else
        {
            $condition = $entityTypeCondition;
        }
        
        $properties = new DataClassProperties();
        
        $properties->add(new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_ID));
        $properties->add(new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_NAME));
        $properties->add(new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_DESCRIPTION));
        $properties->add(new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_CODE));
        
        $properties->add(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_STATUS));
        
        $joins = self::get_course_rel_group_joins();
        $parameters = new RecordRetrievesParameters($properties, $condition, $count, $offset, $order_property, $joins);
        
        return self::records(CourseEntityRelation::class_name(), $parameters);
    }

    /**
     * Counts all groups directly subscribed to a course.
     * 
     * @param $condition Condition
     *
     * @return int
     */
    public static function count_groups_directly_subscribed_to_course($condition = null)
    {
        $entityTypeCondition = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_GROUP));
        
        if ($condition instanceof Condition)
        {
            $conditions = array();
            $conditions[] = $condition;
            $conditions[] = $entityTypeCondition;
            
            $condition = new AndCondition($conditions);
        }
        else
        {
            $condition = $entityTypeCondition;
        }
        
        $parameters = new DataClassCountParameters($condition, self::get_course_rel_group_joins());
        
        return self::count(CourseEntityRelation::class_name(), $parameters);
    }

    /**
     * Retrieves all the platform groups subscribed in the given courses
     * 
     * @param array $course_ids
     *
     * @return ResultSet<Group>
     */
    public static function retrieve_all_subscribed_platform_groups($course_ids)
    {
        $left_value_variable = new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_LEFT_VALUE);
        $right_value_variable = new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_RIGHT_VALUE);
        
        $properties = new DataClassProperties();
        
        $properties->add($left_value_variable);
        $properties->add($right_value_variable);
        
        $conditions = array();
        $conditions[] = new InCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_COURSE_ID), 
            $course_ids);
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_GROUP));
        $condition = new AndCondition($conditions);
        
        $joins = self::get_course_rel_group_joins();
        
        $parameters = new RecordRetrievesParameters($properties, $condition, null, null, array(), $joins);
        
        $direct_subscribed_groups = self::records(CourseEntityRelation::class_name(), $parameters);
        
        /**
         * Make sure that no results are returned, because otherwise no conditions would be build and all platform
         * groups would be retrieved
         */
        if ($direct_subscribed_groups->size() == 0)
        {
            return new EmptyResultSet();
        }
        
        while ($direct_subscribed_group = $direct_subscribed_groups->next_result())
        {
            $and_conditions = array();
            
            $and_conditions[] = new InequalityCondition(
                $left_value_variable, 
                InequalityCondition::GREATER_THAN_OR_EQUAL, 
                new StaticConditionVariable($direct_subscribed_group[Group::PROPERTY_LEFT_VALUE]));
            
            $and_conditions[] = new InequalityCondition(
                $right_value_variable, 
                InequalityCondition::LESS_THAN_OR_EQUAL, 
                new StaticConditionVariable($direct_subscribed_group[Group::PROPERTY_RIGHT_VALUE]));
            
            $sub_conditions[] = new AndCondition($and_conditions);
        }
        
        $condition = new OrCondition($sub_conditions);
        
        return \Chamilo\Core\Group\Storage\DataManager::retrieves(
            Group::class_name(), 
            new DataClassRetrievesParameters($condition));
    }

    /**
     * Subscribes a group to a course
     * 
     * @param int $course_id
     * @param int $group_id
     * @param int $status
     *
     * @return bool
     */
    public static function subscribe_group_to_course($course_id, $group_id, $status)
    {
        $courseEntityRelation = new CourseEntityRelation();
        
        $courseEntityRelation->set_course_id($course_id);
        $courseEntityRelation->setEntityId($group_id);
        $courseEntityRelation->setEntityType(CourseEntityRelation::ENTITY_TYPE_GROUP);
        $courseEntityRelation->set_status($status);
        
        return $courseEntityRelation->create();
    }

    /**
     * Unsubscribes a group from a given course
     * 
     * @param int $course_id
     * @param int $group_id
     *
     * @return bool
     */
    public static function unsubscribe_group_from_course($course_id, $group_id)
    {
        return self::deletes(
            CourseEntityRelation::class_name(), 
            self::get_course_group_relation_by_course_and_group_condition($course_id, $group_id));
    }

    /**
     * Retrieves all groups that are admins in the given course.
     * 
     * @param int $course_id
     *
     * @return ResultSet
     */
    public static function retrieve_groups_subscribed_as_teacher($course_id)
    {
        return self::retrieve_groups_directly_subscribed_to_course_as_status(
            $course_id, 
            CourseEntityRelation::STATUS_TEACHER);
    }

    /**
     * Retrieves all groups that are admins in the given course.
     * 
     * @param int $course_id
     *
     * @return ResultSet
     */
    public static function retrieve_groups_subscribed_as_student($course_id)
    {
        return self::retrieve_groups_directly_subscribed_to_course_as_status(
            $course_id, 
            CourseEntityRelation::STATUS_STUDENT);
    }

    /**
     * Retrieves all groups that are admins in the given course.
     * 
     * @param int $course_id
     * @param int $status
     *
     * @return ResultSet
     */
    public static function retrieve_groups_directly_subscribed_to_course_as_status($course_id, 
        $status = CourseEntityRelation::STATUS_STUDENT)
    {
        $conditions = array();
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_COURSE_ID), 
            new StaticConditionVariable($course_id));
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_STATUS), 
            new StaticConditionVariable($status));
        
        return self::retrieve_groups_directly_subscribed_to_course(new AndCondition($conditions));
    }

    /**
     * **************************************************************************************************************
     * CourseEntityRelation Helper Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Returns the joins for the groups that are directly subscribed to a course
     * 
     * @return Joins
     */
    protected static function get_course_rel_group_joins()
    {
        $joinConditions = array();
        $joinConditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_ID), 
            new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_ID));
        $joinConditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_GROUP));
        
        $joins = new Joins();
        $joins->add(new Join(Group::class_name(), new AndCondition($joinConditions)));
        
        return $joins;
    }

    /**
     * Returns the condition for the get course user relation by course and user
     * 
     * @param int $course_id
     * @param int $group_id
     *
     * @return Condition
     */
    protected static function get_course_group_relation_by_course_and_group_condition($course_id, $group_id)
    {
        $conditions = array();
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_ID), 
            new StaticConditionVariable($group_id));
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
            new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_GROUP));
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(CourseEntityRelation::class_name(), CourseEntityRelation::PROPERTY_COURSE_ID), 
            new StaticConditionVariable($course_id));
        
        return new AndCondition($conditions);
    }

    /**
     * **************************************************************************************************************
     * Course Subscription Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Checks if a given user is subscribed to a course directly or through (sub)groups
     * 
     * @param Course $course
     * @param \core\user\storage\data_class\User $user
     *
     * @return bool
     */
    public static function is_subscribed($course, $user)
    {
        $course_id = $course;
        if ($course instanceof Course)
        {
            $course_id = $course->get_id();
        }
        
        if (! $user instanceof \Chamilo\Core\User\Storage\DataClass\User)
        {
            $user = \Chamilo\Core\User\Storage\DataManager::retrieve_by_id(User::class_name(), $user);
        }
        
        if (is_null(self::$is_subscribed_cache[$course_id][$user->get_id()]))
        {
            $has_user_relations = self::is_user_direct_subscribed_to_course($user->get_id(), $course_id);
            
            $groups = $user->get_groups(true);
            if ($groups)
            {
                $conditions = array();
                
                $conditions[] = new InCondition(
                    new PropertyConditionVariable(
                        CourseEntityRelation::class_name(), 
                        CourseEntityRelation::PROPERTY_ENTITY_ID), 
                    $user->get_groups(true));
                
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        CourseEntityRelation::class_name(), 
                        CourseEntityRelation::PROPERTY_ENTITY_TYPE), 
                    new StaticConditionVariable(CourseEntityRelation::ENTITY_TYPE_GROUP));
                
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        CourseEntityRelation::class_name(), 
                        CourseEntityRelation::PROPERTY_COURSE_ID), 
                    new StaticConditionVariable($course_id));
                
                $condition = new AndCondition($conditions);
                
                $has_group_relations = self::count(CourseEntityRelation::class_name(), $condition) > 0;
            }
            else
            {
                $has_group_relations = false;
            }
            
            self::$is_subscribed_cache[$course_id][$user->get_id()] = $has_user_relations || $has_group_relations;
        }
        
        return self::$is_subscribed_cache[$course_id][$user->get_id()];
    }

    /**
     * **************************************************************************************************************
     * CourseRelCourseSetting Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Copies the course settings to all the courses from a given course type can be limited by a given course setting
     * 
     * @param int $course_type_id int
     * @param int $course_setting_id - [OPTIONAL] default null
     * @return bool
     */
    public static function copy_course_settings_from_course_type($course_type_id, $course_setting_id = null)
    {
        $course_settings_controller = CourseSettingsController::getInstance();
        
        $course_setting_relations = self::retrieve_course_setting_relations_from_course_type(
            $course_type_id, 
            $course_setting_id);
        
        $succes = true;
        
        while ($course_setting_relation = $course_setting_relations->next_result())
        {
            $course_setting_id = $course_setting_relation->get_course_setting_id();
            $course_setting = $course_settings_controller->get_course_setting_by_id($course_setting_id);
            
            $values = $course_settings_controller->get_course_type_setting(
                $course_type_id, 
                $course_setting[CourseSetting::PROPERTY_NAME], 
                $course_setting[CourseSetting::PROPERTY_TOOL_ID]);
            
            $values = (array) $values;
            
            foreach ($values as $value)
            {
                try
                {
                    $course_setting_relation->add_course_setting_value($value);
                }
                catch (\Exception $e)
                {
                    $succes = false;
                }
            }
        }
        
        return $succes;
    }

    /**
     * Returns the course setting relations for a given course type Can be limited by a given course setting
     * 
     * @param $course_type_id int - [OPTIONAL] default 0
     * @param $course_setting_id int - [OPTIONAL] default null
     * @return ResultSet<CourseRelCourseSetting>
     */
    public static function retrieve_course_setting_relations_from_course_type($course_type_id = 0, 
        $course_setting_id = null)
    {
        $condition = self::get_condition_for_course_settings_from_course_type($course_type_id, $course_setting_id);
        
        return self::retrieves(CourseRelCourseSetting::class_name(), new DataClassRetrievesParameters($condition));
    }

    /**
     * **************************************************************************************************************
     * CourseRelCourseSetting Helper Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Returns the condition for the course settings for all the courses connected to a given course type Can be limited
     * by a given course setting
     * 
     * @param $course_type_id int - [OPTIONAL] default 0
     * @param $course_setting_id int - [OPTIONAL] default null
     * @return Condition
     */
    public static function get_condition_for_course_settings_from_course_type($course_type_id = 0, 
        $course_setting_id = null)
    {
        $courses_condition = new EqualityCondition(
            new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_COURSE_TYPE_ID), 
            new StaticConditionVariable($course_type_id));
        
        /**
         * Course rel course setting conditions
         */
        $course_rel_course_setting_conditions = array();
        
        $course_rel_course_setting_conditions[] = new SubselectCondition(
            new PropertyConditionVariable(
                CourseRelCourseSetting::class_name(), 
                CourseRelCourseSetting::PROPERTY_COURSE_ID), 
            new PropertyConditionVariable(Course::class_name(), CourseRelCourseSetting::PROPERTY_ID), 
            Course::get_table_name(), 
            $courses_condition);
        
        if (! is_null($course_setting_id))
        {
            $course_rel_course_setting_conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    CourseRelCourseSetting::class_name(), 
                    CourseRelCourseSetting::PROPERTY_COURSE_SETTING_ID), 
                new StaticConditionVariable($course_setting_id));
        }
        
        return new AndCondition($course_rel_course_setting_conditions);
    }

    /**
     * **************************************************************************************************************
     * CourseSetting Helper Functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Set the tool visible = 1 / invisible = 0 by course_id and tool_name if tool_name is null, all the tools in the
     * course will be set (except Home tool)
     * 
     * @param int $course_id
     * @param string $tool_name
     * @param int $visible
     */
    public static function set_tool_visibility_by_tool_name($course_id, $tool_name = null, $visible = 1)
    {
        $tools_condition = new NotCondition(
            new EqualityCondition(
                new PropertyConditionVariable(CourseTool::class_name(), CourseTool::PROPERTY_SECTION_TYPE), 
                new StaticConditionVariable(CourseSection::TYPE_CUSTOM)));
        
        $tools = \Chamilo\Application\Weblcms\Storage\DataManager::retrieves(
            CourseTool::class_name(), 
            new DataClassRetrievesParameters($tools_condition));
        
        while ($tool = $tools->next_result())
        {
            if (! $tool_name || $tool_name == $tool->get_name())
            {
                self::set_tool_visibility_by_tool_id($course_id, $tool->get_id(), $visible);
            }
        }
    }

    /**
     * Sets the tool visibility by a given course and tool id
     * 
     * @param int $course_id
     * @param int $tool_id
     * @param int $visible
     */
    public static function set_tool_visibility_by_tool_id($course_id, $tool_id, $visible = 1)
    {
        $course_settings_controller = CourseSettingsController::getInstance();
        
        $course_setting = $course_settings_controller->get_course_setting_object_from_name_and_tool(
            CourseSetting::COURSE_SETTING_TOOL_VISIBLE, 
            $tool_id);
        
        if ($course_setting)
        {
            $course = self::retrieve_by_id(Course::class_name(), $course_id);
            if ($course)
            {
                $course_setting_relation = $course->retrieve_course_setting_relation($course_setting);
                
                if ($course_setting_relation)
                {
                    $course_setting_relation->truncate_values();
                    $course_setting_relation->add_course_setting_value($visible);
                }
            }
        }
    }

    /**
     * Retrieves all course users of a given course with status and subscription type
     * 
     * @param int $course_id
     * @param \libraries\storage\Condition $condition
     * @param int $offset
     * @param int $count
     * @param \libraries\storage\OrderBy[] $order_property
     *
     * @return \application\weblcms\course\RecordResultSet
     */
    public static function retrieve_all_course_users($course_id, $condition = null, $offset = null, $count = null, 
        $order_property = null)
    {
        $extension = new DoctrineExtension(self::getInstance());
        
        return $extension->retrieve_all_course_users($course_id, $condition, $offset, $count, $order_property);
    }

    /**
     * Counts all course users of a given course with status and subscription type
     * 
     * @param int $course_id
     * @param Condition $condition
     *
     * @throws \libraries\storage\DataClassNoResultException
     *
     * @return int
     */
    public static function count_all_course_users($course_id, $condition = null)
    {
        $extension = new DoctrineExtension(self::getInstance());
        
        return $extension->count_all_course_users($course_id, $condition);
    }

    /**
     * **************************************************************************************************************
     * Courses with course categories
     * **************************************************************************************************************
     */
    
    /**
     * Retrieves all the courses with course categories for a given user
     * 
     * @param User $user
     *
     * @return RecordResultSet
     */
    public static function retrieve_all_courses_with_course_categories(User $user)
    {
        $course_ids = self::getSubscribedCourseIdentifiersByRelation($user);
        
        return self::retrieve_courses_with_course_categories_by_ids($user->getId(), $course_ids);
    }

    /**
     * Retrieves courses with course categories for a given user, optionally limiting the result by a given condition
     * 
     * @param User $user
     * @param Condition $condition
     *
     * @return RecordResultSet
     */
    public static function retrieve_courses_with_user_course_categories(User $user, Condition $condition = null)
    {
        $course_ids = self::getSubscribedCourseIdentifiersByRelation($user);
        
        return self::retrieve_courses_with_course_categories_by_ids($user->get_id(), $course_ids, $condition);
    }

    /**
     * Retrieves courses with user course categories by course ids
     * 
     * @param int $user_id
     * @param array $course_ids
     * @param Condition $condition
     *
     * @return RecordResultSet
     */
    public static function retrieve_courses_with_course_categories_by_ids($user_id, array $course_ids = array(), 
        Condition $condition = null)
    {
        if (count($course_ids) > 0)
        {
            $properties = new DataClassProperties();
            
            $properties->add(new PropertiesConditionVariable(Course::class_name()));
            $properties->add(
                new PropertyConditionVariable(
                    CourseTypeUserCategoryRelCourse::class_name(), 
                    CourseTypeUserCategoryRelCourse::PROPERTY_COURSE_TYPE_USER_CATEGORY_ID));
            $properties->add(
                new PropertyConditionVariable(
                    CourseTypeUserCategoryRelCourse::class_name(), 
                    CourseTypeUserCategoryRelCourse::PROPERTY_SORT));
            
            $join_conditions = array();
            
            $join_conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    CourseTypeUserCategoryRelCourse::class_name(), 
                    CourseTypeUserCategoryRelCourse::PROPERTY_USER_ID), 
                new StaticConditionVariable($user_id));
            
            $join_conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    CourseTypeUserCategoryRelCourse::class_name(), 
                    CourseTypeUserCategoryRelCourse::PROPERTY_COURSE_ID), 
                new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_ID));
            
            $join = new Join(
                CourseTypeUserCategoryRelCourse::class_name(), 
                new AndCondition($join_conditions), 
                Join::TYPE_LEFT);
            
            $conditions = array();
            
            $conditions[] = new InCondition(
                new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_ID), 
                $course_ids);
            
            if ($condition)
            {
                $conditions[] = $condition;
            }
            
            $order_by = array();
            
            $order_by[] = new OrderBy(
                new PropertyConditionVariable(
                    CourseTypeUserCategoryRelCourse::class_name(), 
                    CourseTypeUserCategoryRelCourse::PROPERTY_SORT), 
                SORT_ASC);
            
            $order_by[] = new OrderBy(
                new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_TITLE), 
                SORT_ASC);
            
            $parameters = new RecordRetrievesParameters($properties, new AndCondition($conditions), /* $count = */
                null, /* $offset = */
                null, $order_by, new Joins(array($join)));
            
            $courses = self::records(Course::class_name(), $parameters);
            
            return $courses;
        }
        
        return new RecordResultSet(array());
    }
}
