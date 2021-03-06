<?php
namespace Chamilo\Core\Repository\ContentObject\FillInBlanksQuestion\Storage\DataClass;

use Chamilo\Core\Repository\ContentObject\FillInBlanksQuestion\Form\FillInBlanksQuestionForm;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;

/**
 * $Id: fill_in_blanks_question.class.php 200 2009-11-13 12:30:04Z kariboe $
 * 
 * @package repository.lib.question_types.fill_in_blanks_question
 */
class FillInBlanksQuestion extends ContentObject implements Versionable
{

    private $answers;
    
    // const PROPERTY_ANSWERS = 'answers';
    const PROPERTY_ANSWER_TEXT = 'answer_text';
    const PROPERTY_CASE_SENSITIVE = 'case_sensitive';
    const PROPERTY_QUESTION_TYPE = 'question_type';
    const PROPERTY_FIELD_OPTION = 'field_option';
    const PROPERTY_DEFAULT_POSITIVE_SCORE = 'default_positive_score';
    const PROPERTY_DEFAULT_NEGATIVE_SCORE = 'default_negative_score';
    const PROPERTY_SHOW_INLINE = 'show_inline';
    const TYPE_SIZED_TEXT = 0;
    const TYPE_UNIFORM_TEXT = 1;
    const TYPE_SELECT = 2;
    const DEFAULT_INPUT_ANSWER_TEXT = '';
    const DEFAULT_CASE_SENSITIVE = true;
    const DEFAULT_INPUT_TYPE = self::TYPE_SIZED_TEXT;
    const DEFAULT_POSITIVE_SCORE = 1;
    const DEFAULT_NEGATIVE_SCORE = 0;
    const DEFAULT_SHOW_INLINE = true;
    const MARK_WRONG = 0;
    const MARK_CORRECT = 1;
    const MARK_MAX = 2;
    const HINT_CHARACTER = 1;
    const HINT_ANSWER = 2;
    
    // we need a special css class that sets the inputfield with a monospaced font, needed to make the *magic* of fixed
    // size fields work :-)
    const TEXT_INPUT_FIELD_CSS_CLASS = 'fill_in_the_blanks_input_field';

    public static function get_type_name()
    {
        return ClassnameUtilities::getInstance()->getClassNameFromNamespace(self::class_name(), true);
        ;
    }

    /*
     * public function add_answer($answer) { $answers = $this->get_answers(); $answers[] = $answer; return
     * $this->set_additional_property(self :: PROPERTY_ANSWERS, serialize($answers)); } public function
     * set_answers($answers) { return $this->set_additional_property(self :: PROPERTY_ANSWERS, serialize($answers)); }
     */
    public function get_answers($index = -1)
    {
        $text = $this->get_answer_text();
        
        if (! $this->answers)
        {
            $this->answers = FillInBlanksQuestionAnswer::parse($text, $this->get_default_positive_score());
        }
        
        if ($index < 0)
        {
            $result = $this->answers;
        }
        else
        {
            $result = array();
            foreach ($this->answers as $answer)
            {
                if ($answer->get_position() == $index)
                {
                    $result[] = $answer;
                }
            }
        }
        return $result;
    }

    public function count_positive_answers($index)
    {
        $count = 0;
        
        foreach ($this->get_answers($index) as $answer)
        {
            if ($answer->get_weight() > 0)
            {
                $count ++;
            }
        }
        
        return $count;
    }

    public function get_best_answer_for_question($index)
    {
        return FillInBlanksQuestionAnswer::get_best_answer($this->get_answers($index));
    }

    public function get_hint_for_question($type, $index)
    {
        $answer = $this->get_best_answer_for_question($index);
        
        switch ($type)
        {
            case self::HINT_CHARACTER :
                if ($this->get_question_type() == self::TYPE_SELECT)
                {
                    return '';
                }
                else
                {
                    return substr($answer->get_value(), 0, 1);
                }
                break;
            case self::HINT_ANSWER :
                return $answer->get_hint();
                break;
        }
    }

    public function get_number_of_questions()
    {
        $text = $this->get_answer_text();
        return FillInBlanksQuestionAnswer::get_number_of_questions($text);
    }

    public function get_answer_text()
    {
        return $this->get_additional_property(self::PROPERTY_ANSWER_TEXT);
    }

    public function set_answer_text($answer_text)
    {
        $this->set_additional_property(self::PROPERTY_ANSWER_TEXT, $answer_text);
        unset($this->answers);
    }

    public function get_case_sensitive()
    {
        return $this->get_additional_property(self::PROPERTY_CASE_SENSITIVE);
    }

    public function set_case_sensitive($sensitive)
    {
        $this->set_additional_property(self::PROPERTY_CASE_SENSITIVE, $sensitive);
    }

    public function get_question_type()
    {
        return $this->get_additional_property(self::PROPERTY_QUESTION_TYPE);
    }

    public function set_question_type($question_type)
    {
        $this->set_additional_property(self::PROPERTY_QUESTION_TYPE, $question_type);
    }

    public function get_field_option()
    {
        return $this->get_additional_property(self::PROPERTY_FIELD_OPTION);
    }

    public function set_field_option($option)
    {
        $this->set_additional_property(self::PROPERTY_FIELD_OPTION, $option);
    }

    public function get_default_positive_score()
    {
        return $this->get_additional_property(self::PROPERTY_DEFAULT_POSITIVE_SCORE);
    }

    public function set_default_positive_score($score)
    {
        $this->set_additional_property(self::PROPERTY_DEFAULT_POSITIVE_SCORE, $score);
    }

    public function get_default_negative_score()
    {
        return $this->get_additional_property(self::PROPERTY_DEFAULT_NEGATIVE_SCORE);
    }

    public function set_default_negative_score($score)
    {
        $this->set_additional_property(self::PROPERTY_DEFAULT_NEGATIVE_SCORE, $score);
    }

    public function get_show_inline()
    {
        return $this->get_additional_property(self::PROPERTY_SHOW_INLINE);
    }

    public function set_show_inline($setting)
    {
        $this->set_additional_property(self::PROPERTY_SHOW_INLINE, $setting);
    }

    /**
     * Returns the maximum weight/score a user can receive.
     */
    public function get_maximum_score()
    {
        $maximum = array();
        $answers = $this->get_answers();
        foreach ($answers as $answer)
        {
            $position = $answer->get_position();
            $weight = $answer->get_weight();
            if (! isset($maximum[$position]))
            {
                $maximum[$position] = $weight;
            }
            else
            {
                $maximum[$position] = max($maximum[$position], $weight);
            }
        }
        
        $result = 0;
        
        foreach ($maximum as $weight)
        {
            $result += $weight;
        }
        
        return $result;
    }

    /**
     * Returns the maximum weight for a specific question.
     * 
     * @param int index question's index
     * @return maximum possible weight for the question
     */
    public function get_question_maximum_weight($index)
    {
        $result = 0;
        $answers = $this->get_answers();
        foreach ($answers as $answer)
        {
            $position = $answer->get_position();
            if ($position == $index)
            {
                $weight = $answer->get_weight();
                $result = max($result, $weight);
            }
        }
        return $result;
    }

    public function get_longest_answer($index = null)
    {
        $answers = $this->get_answers();
        $longest = new FillInBlanksQuestionAnswer();
        
        foreach ($answers as $answer)
        {
            if (isset($index))
            {
                $position = $answer->get_position();
                if ($position != $index)
                {
                    // skip answer
                    continue;
                }
            }
            
            // process after indexcheck
            if ($answer->get_size() > $longest->get_size())
            {
                $longest = $answer;
            }
        }
        
        return $longest;
    }

    /**
     * Calculates the size of the inputfield based on the internal parameters.
     * 
     * @param $index
     * @return int size
     */
    public function get_input_field_size($index)
    {
        // todo check for regex (extended functionality)
        if ($this->get_question_type() == FillInBlanksQuestion::TYPE_SIZED_TEXT)
        {
            // sized inputfield
            // get the manipulation value
            $option = $this->get_field_option();
            if (is_null($option))
            {
                $option = 0;
            }
            
            // calculate a random increment based on the manipulation value
            $random_increment = mt_rand(0, $option);
            
            // determine size, and add random increment
            $best = $this->get_best_answer_for_question($index);
            $size = $best->get_size();
            
            if (! $size)
            {
                $size = FillInBlanksQuestionForm::DEFAULT_FIELD_OPTION_SIZE;
            }
            
            $size += $random_increment;
            return $size;
        }
        elseif ($this->get_question_type() == FillInBlanksQuestion::TYPE_UNIFORM_TEXT)
        {
            // uniform inputfield
            $option = $this->get_field_option();
            switch ($option)
            {
                case FillInBlanksQuestionForm::UNIFORM_UNLIMITED_ANSWER :
                    $size = FillInBlanksQuestionForm::DEFAULT_FIELD_OPTION_SIZE; // todo implement some other
                                                                                   // constant?
                case FillInBlanksQuestionForm::UNIFORM_LONGEST_ANSWER :
                    $size = $this->get_longest_answer()->get_size();
                    break;
                default :
                    $size = $option;
                    break;
            }
            
            if (! $size)
            {
                $size = FillInBlanksQuestionForm::DEFAULT_FIELD_OPTION_SIZE;
            }
        }
        else
        {
            $size = FillInBlanksQuestionForm::DEFAULT_FIELD_OPTION_SIZE;
        }
        
        return $size;
    }

    /**
     * Returns one of the MARK_ constants of this class indicating the correctness of the answer.
     * 
     * @param $question_index
     * @param $answer
     * @return constant: If the score is the max, MARK_MAX will be returned; if the score is negative, lower than or
     *         equal to the default negative score, MARK_WRONG is returned; In any other case the MARK_CORRECT is
     *         returned.
     */
    public function is_correct($question_index, $answer)
    {
        $weight = $this->get_weight_from_answer($question_index, $answer);
        $max_question_weight = $this->get_question_maximum_weight($question_index);
        
        if ($weight == $max_question_weight)
        {
            $score = self::MARK_MAX;
        }
        elseif ($weight < 0 || $weight <= $this->get_default_negative_score())
        {
            $score = self::MARK_WRONG;
        }
        else
        {
            $score = self::MARK_CORRECT;
        }
        
        return $score;
    }

    /**
     * Will evaluate all answers and return the highest possible score.
     * 
     * @param int $question_index
     * @param string $answer
     * @return int
     */
    public function get_weight_from_answer($question_index, $answer)
    {
        $answers = $this->get_answers();
        $case_sensitive = $this->get_case_sensitive();
        $scores = array();
        foreach ($answers as $a)
        {
            if ($a->get_position() == $question_index)
            {
                if ($a->evaluate($answer, $case_sensitive))
                {
                    $scores[] = $a->get_weight();
                }
            }
        }
        
        if (! empty($scores))
        {
            return max($scores);
        }
        
        return $this->get_default_negative_score();
    }

    public function get_answer_object($question_index, $answer)
    {
        $answers = $this->get_answers();
        $case_sensitive = $this->get_case_sensitive();
        foreach ($answers as $a)
        {
            if ($a->get_position() == $question_index && $a->evaluate($answer, $case_sensitive))
            {
                return $a;
            }
        }
        return null;
    }

    public static function get_additional_property_names()
    {
        return array(
            self::PROPERTY_ANSWER_TEXT, 
            self::PROPERTY_CASE_SENSITIVE, 
            self::PROPERTY_QUESTION_TYPE, 
            self::PROPERTY_FIELD_OPTION, 
            self::PROPERTY_DEFAULT_POSITIVE_SCORE, 
            self::PROPERTY_DEFAULT_NEGATIVE_SCORE, 
            self::PROPERTY_SHOW_INLINE);
    }

    public function has_comment()
    {
        foreach ($this->get_answers() as $option)
        {
            if ($option->has_comment())
            {
                return true;
            }
        }
        
        return false;
    }
    
    // TODO: should be moved to an additional parent layer "question" which offers a default implementation.
    public function get_default_weight()
    {
        return $this->get_maximum_score();
    }
}
