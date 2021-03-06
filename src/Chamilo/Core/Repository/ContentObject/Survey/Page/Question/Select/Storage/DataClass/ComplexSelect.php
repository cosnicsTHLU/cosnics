<?php
namespace Chamilo\Core\Repository\ContentObject\Survey\Page\Question\Select\Storage\DataClass;

use Chamilo\Core\Repository\ContentObject\Survey\Page\Display\Interfaces\PageDisplayItem;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;

/**
 *
 * @package repository.content_object.survey_select_question
 * @author Eduard Vossen
 * @author Magali Gillard
 */
/**
 * This class represents a complex survey (used to create complex learning objects)
 */
class ComplexSelect extends ComplexContentObjectItem implements PageDisplayItem
{
    const PROPERTY_VISIBLE = 'visible';

    static function get_additional_property_names()
    {
        return array(self::PROPERTY_VISIBLE);
    }

    function get_visible()
    {
        return $this->get_additional_property(self::PROPERTY_VISIBLE);
    }

    function set_visible($value)
    {
        $this->set_additional_property(self::PROPERTY_VISIBLE, $value);
    }

    function is_visible()
    {
        return $this->get_visible() == 1;
    }

    function toggle_visibility()
    {
        $this->set_visible(! $this->get_visible());
    }

    public function getAnswerIds($prefix = null)
    {
        if ($prefix)
        {
            $answerId = $prefix . '_' . $this->getId();
        }
        else
        {
            $answerId = $this->getId();
        }
        return array($answerId);
    }

    function getDataAttributes()
    {
        return null;
    }
}
?>