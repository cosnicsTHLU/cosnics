<?php
namespace Chamilo\Core\Repository\ContentObject\Survey\Page\Question\DateTime\Form;

use Chamilo\Core\Repository\ContentObject\Survey\Page\Question\DateTime\Storage\DataClass\ComplexDateTime;
use Chamilo\Core\Repository\Form\ComplexContentObjectItemForm;
use Chamilo\Libraries\Platform\Translation;

/**
 *
 * @package repository.content_object.survey_date_time_question
 * @author Eduard Vossen
 * @author Magali Gillard
 */
class ComplexDateTimeForm extends ComplexContentObjectItemForm
{

    public function get_elements()
    {
        $elements[] = $this->createElement(
            'checkbox', 
            ComplexDateTime :: PROPERTY_VISIBLE, 
            Translation :: get('Visible'));
        return $elements;
    }

    function get_default_values()
    {
        $cloi = $this->get_complex_content_object_item();
        
        if (isset($cloi))
        {
            $defaults[ComplexDateTime :: PROPERTY_VISIBLE] = $cloi->get_visible();
        }
        
        return $defaults;
    }
    
    // Inherited
    function create_complex_content_object_item()
    {
        $cloi = $this->get_complex_content_object_item();
        $values = $this->exportValues();
        $cloi->set_visible($values[ComplexDateTime :: PROPERTY_VISIBLE]);
        return parent :: create_complex_content_object_item();
    }

    function create_cloi_from_values($values)
    {
        $cloi = $this->get_complex_content_object_item();
        $cloi->set_visible($values[ComplexDateTime :: PROPERTY_VISIBLE]);
        return parent :: create_complex_content_object_item();
    }

    function update_cloi_from_values($values)
    {
        $cloi = $this->get_complex_content_object_item();
        $cloi->set_visible($values[ComplexDateTime :: PROPERTY_VISIBLE]);
        return parent :: update_complex_content_object_item();
    }
    
    // Inherited
    function update_complex_content_object_item()
    {
        $cloi = $this->get_complex_content_object_item();
        $values = $this->exportValues();
        $cloi->set_visible($values[ComplexDateTime :: PROPERTY_VISIBLE]);
        return parent :: update_complex_content_object_item();
    }
}

?>