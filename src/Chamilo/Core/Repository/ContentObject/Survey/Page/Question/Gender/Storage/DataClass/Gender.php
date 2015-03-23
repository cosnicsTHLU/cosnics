<?php
namespace Chamilo\Core\Repository\ContentObject\Survey\Page\Question\Gender\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;

class Gender extends ContentObject implements Versionable
{
    const CLASS_NAME = __CLASS__;
    const PROPERTY_QUESTION = 'question';
    const PROPERTY_INSTRUCTION = 'instruction';

    static function get_type_name()
    {
        return ClassnameUtilities :: getInstance()->getClassNameFromNamespace(self :: CLASS_NAME, true);
    }

    public function get_question()
    {
        return $this->get_additional_property(self :: PROPERTY_QUESTION);
    }

    public function set_question($question)
    {
        return $this->set_additional_property(self :: PROPERTY_QUESTION, $question);
    }

    public function get_instruction()
    {
        return $this->get_additional_property(self :: PROPERTY_INSTRUCTION);
    }

    public function set_instruction($instruction)
    {
        return $this->set_additional_property(self :: PROPERTY_INSTRUCTION, $instruction);
    }

    public function has_instruction()
    {
        $instruction = $this->get_instruction();
        return ($instruction != '<p>&#160;</p>' && count($instruction) > 0);
    }

    static function get_additional_property_names()
    {
        return array(self :: PROPERTY_QUESTION, self :: PROPERTY_INSTRUCTION);
    }
}

?>