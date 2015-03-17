<?php
namespace Chamilo\Core\Repository\ContentObject\Assessment\Display\Component\Viewer\Wizard\Inc;

use Chamilo\Core\Repository\Common\ContentObjectResourceRenderer;
use Chamilo\Libraries\Architecture\ClassnameUtilities;

/**
 * $Id: question_display.class.php 200 2009-11-13 12:30:04Z kariboe $
 *
 * @package repository.lib.complex_display.assessment.component.viewer.wizard.inc
 */
abstract class QuestionDisplay
{

    private $complex_content_object_question;

    private $question;

    private $question_nr;

    private $formvalidator;

    private $renderer;

    public function __construct($formvalidator, $complex_content_object_question, $question_nr, $question)
    {
        $this->formvalidator = $formvalidator;
        $this->renderer = $formvalidator->defaultRenderer();

        $this->complex_content_object_question = $complex_content_object_question;
        $this->question_nr = $question_nr;
        $this->question = $question;
    }

    public function get_attempt()
    {
        return $this->get_formvalidator()->get_assessment_viewer()->get_assessment_question_attempt(
            $this->get_complex_content_object_question()->get_id());
    }

    public function get_configuration()
    {
        return $this->get_formvalidator()->get_assessment_viewer()->get_configuration();
    }

    public function get_answers()
    {
        $attempt = $this->get_attempt();
        if (! is_null($attempt))
        {
            return unserialize($attempt->get_answer());
        }
        else
        {
            return false;
        }
    }

    public function get_complex_content_object_question()
    {
        return $this->complex_content_object_question;
    }

    public function get_question()
    {
        return $this->question;
    }

    public function get_renderer()
    {
        return $this->renderer;
    }

    public function get_formvalidator()
    {
        return $this->formvalidator;
    }

    public function render()
    {
        $formvalidator = $this->formvalidator;
        $this->add_header();

        if ($this->add_borders())
        {
            $header = array();
            $header[] = $this->get_instruction();
            $header[] = '<div class="with_borders">';

            $formvalidator->addElement('html', implode(PHP_EOL, $header));
        }

        $formvalidator->addElement(
            'hidden',
            'hint_question[' . $this->get_complex_content_object_question()->get_id() . ']',
            0);

        $this->add_question_form();

        if ($this->add_borders())
        {
            $footer = array();
            $footer[] = '<div class="clear"></div>';
            $footer[] = '</div>';
            $formvalidator->addElement('html', implode(PHP_EOL, $footer));
        }

        $this->add_footer();
    }

    abstract public function add_question_form();

    public function add_header()
    {
        $formvalidator = $this->formvalidator;
        /*
         * $clo_question = $this->get_clo_question(); $number_of_questions = $formvalidator->get_number_of_questions();
         * $current_question = $this->question_nr;
         */

        $html[] = '<div class="question">';
        $html[] = '<div class="title">';
        $html[] = '<div class="number">';
        $html[] = '<div class="bevel">';
        $html[] = $this->question_nr . '.';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<div class="text">';
        $html[] = '<div class="bevel">';
        $html[] = $this->get_title();
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<div class="clear"></div>';
        $html[] = '</div>';
        $html[] = '<div class="answer">';
        $html[] = $this->get_description();
        $html[] = '<div class="clear"></div>';

        $header = implode(PHP_EOL, $html);
        $formvalidator->addElement('html', $header);
    }

    public function get_title()
    {
        return $this->question->get_title();
    }

    public function get_description()
    {
        $html = array();

        $description = $this->question->get_description();
        if ($this->question->has_description())
        {
            $html[] = '<div class="description">';
            $renderer = new ContentObjectResourceRenderer($this, $description);
            $html[] = $renderer->run();
            $html[] = '<div class="clear">&nbsp;</div>';
            $html[] = '</div>';
        }

        return implode(PHP_EOL, $html);
    }

    public function add_footer($formvalidator)
    {
        $formvalidator = $this->formvalidator;

        $html[] = '</div>';
        $html[] = '</div>';

        $footer = implode(PHP_EOL, $html);
        $formvalidator->addElement('html', $footer);
    }

    public function add_borders()
    {
        return false;
    }

    abstract public function get_instruction();

    public static function factory($formvalidator, $complex_content_object_question, $question_nr)
    {
        $question = \Chamilo\Core\Repository\Storage\DataManager :: retrieve_content_object(
            $complex_content_object_question->get_ref());
        $type = $question->get_type();

        $class = ClassnameUtilities :: getInstance()->getNamespaceFromClassname($type) . '\integration\\' . __NAMESPACE__ .
             '\Display';
        $question_display = new $class($formvalidator, $complex_content_object_question, $question_nr, $question);
        return $question_display;
    }

    /**
     *
     * @author Antonio Ognio @source http://www.php.net/manual/en/function.shuffle.php (06-May-2008 04:42)
     */
    public function shuffle_with_keys($array)
    {
        /*
         * Auxiliary array to hold the new order
         */
        $aux = array();
        /*
         * We work with an array of the keys
         */
        $keys = array_keys($array);
        /*
         * We shuffle the keys
         */
        shuffle($keys);
        /*
         * We iterate thru' the new order of the keys
         */
        foreach ($keys as $key)
        {
            /*
             * We insert the key, value pair in its new order
             */
            $aux[(string) $key] = $array[$key];
            /*
             * We remove the element from the old array to save memory
             */
        }
        /*
         * The auxiliary array with the new order overwrites the old variable
         */
        return $aux;
    }
}
