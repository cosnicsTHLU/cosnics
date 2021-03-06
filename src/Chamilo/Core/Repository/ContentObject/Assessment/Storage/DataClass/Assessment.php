<?php
namespace Chamilo\Core\Repository\ContentObject\Assessment\Storage\DataClass;

use Chamilo\Configuration\Configuration;
use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\ComplexContentObjectSupport;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * This class represents an assessment
 * 
 * @package repository.lib.content_object.assessment
 */
class Assessment extends ContentObject implements ComplexContentObjectSupport
{
    const PROPERTY_TIMES_TAKEN = 'times_taken';
    const PROPERTY_AVERAGE_SCORE = 'average_score';
    const PROPERTY_MAXIMUM_SCORE = 'maximum_score';
    const PROPERTY_MAXIMUM_ATTEMPTS = 'max_attempts';
    const PROPERTY_QUESTIONS_PER_PAGE = 'questions_per_page';
    const PROPERTY_MAXIMUM_TIME = 'max_time';
    const PROPERTY_RANDOM_QUESTIONS = 'random_questions';

    /**
     * The number of questions in this assessment
     * 
     * @var int
     */
    private $question_count;

    /**
     * An ObjectResultSet containing all ComplexContentObjectItem objects for individual questions.
     * 
     * @var ObjectResultSet
     */
    private $questions;

    public static function get_type_name()
    {
        return ClassnameUtilities::getInstance()->getClassNameFromNamespace(self::class_name(), true);
    }

    public static function get_additional_property_names()
    {
        return array(
            self::PROPERTY_MAXIMUM_ATTEMPTS, 
            self::PROPERTY_QUESTIONS_PER_PAGE, 
            self::PROPERTY_MAXIMUM_TIME, 
            self::PROPERTY_RANDOM_QUESTIONS);
    }

    public function set_assessment_type($type)
    {
        $this->set_additional_property(self::PROPERTY_ASSESSMENT_TYPE, $type);
    }

    public function get_maximum_attempts()
    {
        return $this->get_additional_property(self::PROPERTY_MAXIMUM_ATTEMPTS);
    }

    public function has_unlimited_attempts()
    {
        return $this->get_maximum_attempts() == 0;
    }

    public function set_maximum_attempts($value)
    {
        $this->set_additional_property(self::PROPERTY_MAXIMUM_ATTEMPTS, $value);
    }

    public function get_maximum_time()
    {
        return $this->get_additional_property(self::PROPERTY_MAXIMUM_TIME);
    }

    public function set_maximum_time($value)
    {
        $this->set_additional_property(self::PROPERTY_MAXIMUM_TIME, $value);
    }

    public function get_questions_per_page()
    {
        return $this->get_additional_property(self::PROPERTY_QUESTIONS_PER_PAGE);
    }

    public function set_questions_per_page($value)
    {
        $this->set_additional_property(self::PROPERTY_QUESTIONS_PER_PAGE, $value);
    }

    public function get_random_questions()
    {
        return $this->get_additional_property(self::PROPERTY_RANDOM_QUESTIONS);
    }

    public function set_random_questions($random_questions)
    {
        $this->set_additional_property(self::PROPERTY_RANDOM_QUESTIONS, $random_questions);
    }

    public function get_allowed_types()
    {
        $registrations = Configuration::getInstance()->getIntegrationRegistrations(
            self::package(), 
            \Chamilo\Core\Repository\Manager::package() . '\ContentObject');
        $types = array();
        
        foreach ($registrations as $registration)
        {
            $namespace = ClassnameUtilities::getInstance()->getNamespaceParent(
                $registration[Registration::PROPERTY_CONTEXT], 
                6);
            $types[] = $namespace . '\Storage\DataClass\\' .
                 ClassnameUtilities::getInstance()->getPackageNameFromNamespace($namespace);
        }
        
        return $types;
    }

    public function get_table()
    {
        return self::get_type_name();
    }

    public function count_questions()
    {
        if (! isset($this->question_count))
        {
            $this->question_count = \Chamilo\Core\Repository\Storage\DataManager::count_complex_content_object_items(
                ComplexContentObjectItem::class_name(), 
                new EqualityCondition(
                    new PropertyConditionVariable(
                        ComplexContentObjectItem::class_name(), 
                        ComplexContentObjectItem::PROPERTY_PARENT), 
                    new StaticConditionVariable($this->get_id()), 
                    ComplexContentObjectItem::get_table_name()));
        }
        
        return $this->question_count;
    }

    public function get_questions()
    {
        if (! isset($this->questions))
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(
                    ComplexContentObjectItem::class_name(), 
                    ComplexContentObjectItem::PROPERTY_PARENT), 
                new StaticConditionVariable($this->get_id()), 
                ComplexContentObjectItem::get_table_name());
            $this->questions = \Chamilo\Core\Repository\Storage\DataManager::retrieve_complex_content_object_items(
                ComplexContentObjectItem::class_name(), 
                $condition);
        }
        return $this->questions;
    }

    /**
     * Returns the maximum score for this assessment
     */
    public function get_maximum_score()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem::class_name(), 
                ComplexContentObjectItem::PROPERTY_PARENT), 
            new StaticConditionVariable($this->get_id()));
        
        $clo_questions = \Chamilo\Core\Repository\Storage\DataManager::retrieve_complex_content_object_items(
            $this->get_type_name(), 
            ComplexContentObjectItem::class_name(), 
            $condition);
        
        $maxscore = 0;
        
        while ($clo_question = $clo_questions->next_result())
        {
            $maxscore += $clo_question->get_weight();
        }
        
        return $maxscore;
    }
}
