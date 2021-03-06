<?php
namespace Chamilo\Core\Repository\ContentObject\FrequentlyAskedQuestions\Display\Form;

use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Form\Element\AdvancedElementFinder\AdvancedElementFinderElements;
use Chamilo\Libraries\Format\Form\Element\AdvancedElementFinder\AdvancedElementFinderElementTypes;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * Form to configure the portfolio (sub)item rights
 * 
 * @package repository\content_object\portfolio\display
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class RightsForm extends FormValidator
{
    const PROPERTY_INHERIT = 'inherit';
    const PROPERTY_RIGHT_OPTION = 'right_option';
    const PROPERTY_SUBMIT = 'submit';
    const PROPERTY_RESET = 'reset';
    const PROPERTY_BUTTONS = 'buttons';
    const PROPERTY_TARGETS = 'targets';
    const INHERIT_TRUE = 0;
    const INHERIT_FALSE = 1;
    const RIGHT_OPTION_ALL = 0;
    const RIGHT_OPTION_ME = 1;
    const RIGHT_OPTION_SELECT = 2;

    /**
     * The selected location ids
     * 
     * @var \core\rights\RightLocation[]
     */
    private $locations;

    /**
     * The available rights
     * 
     * @var int[string]
     */
    private $available_rights;

    /**
     * The configurable entities
     * 
     * @var \core\rights\RightsEntity[]
     */
    private $entities;

    /**
     * The currently selected entities
     * 
     * @var \core\rights\RightsLocationEntityRight[]
     */
    private $selected_entities;

    /**
     * Constructor
     * 
     * @param string url
     * @param \core\rights\RightLocation[] $locations
     * @param int[string] $available_rights
     * @param \core\rights\RightsEntity[] $entities
     * @param \core\rights\RightsLocationEntityRight[] $selected_entities
     */
    public function __construct($url, $locations, $available_rights, $entities, $selected_entities)
    {
        parent::__construct('simple_rights_editor', 'post', $url);
        
        $this->locations = $locations;
        $this->entities = $entities;
        $this->available_rights = $available_rights;
        $this->selected_entities = $selected_entities;
        
        $this->build_form();
        
        $this->setDefaults();
    }

    /**
     * Builds the form
     */
    public function build_form()
    {
        $this->build_locations_form();
        $this->build_inheritance_form();
        
        $this->addElement('html', '<div style="display:none;" class="specific_rights_selector_box">');
        
        foreach ($this->available_rights as $right_name => $right_id)
        {
            $this->build_right_form($right_name, $right_id);
        }
        
        $this->addElement('html', '</div>');
        
        $this->build_form_footer();
    }

    /**
     * Build the locations form
     */
    private function build_locations_form()
    {
        if (count($this->locations) > 1)
        {
            $html = array();
            $html[] = '<ul>';
            
            foreach ($this->locations as $location)
            {
                $html[] = '<li>' . $location->get_node()->get_content_object()->get_title();
            }
            
            $html[] = '</ul>';
            
            $this->addElement('category', Translation::get('SelectedFrequentlyAskedQuestionsItems'));
            $this->addElement('html', implode(PHP_EOL, $html));
            $this->addElement('category');
        }
    }

    /**
     * Builds the inheritance form (wheter to inherit the rights from parent location or not)
     */
    private function build_inheritance_form()
    {
        $locations = $this->locations;
        $first_location = $locations[0];
        
        if ($first_location->get_parent_id())
        {
            $has_root_location = false;
        }
        else
        {
            $has_root_location = true;
        }
        
        $this->addElement('category', Translation::get('Inheritance'));
        
        $group = array();
        
        if (! $has_root_location)
        {
            $group[] = & $this->createElement(
                'radio', 
                null, 
                null, 
                Translation::get('InheritRights'), 
                self::INHERIT_TRUE, 
                array('class' => 'inherit_rights_selector'));
        }
        else
        {
            $group[] = & $this->createElement(
                'radio', 
                null, 
                null, 
                Translation::get('InheritRights'), 
                self::INHERIT_TRUE, 
                array('class' => 'inherit_rights_selector', 'disabled' => 'disabled'));
        }
        $group[] = & $this->createElement(
            'radio', 
            null, 
            null, 
            Translation::get('UseSpecificRights'), 
            self::INHERIT_FALSE, 
            array('class' => 'specific_rights_selector'));
        
        $this->addGroup($group, self::PROPERTY_INHERIT, null, '');
        
        $this->addElement('category');
    }

    /**
     * Builds the form for a given right
     * 
     * @param string $right_name
     * @param int $right_id
     */
    private function build_right_form($right_name, $right_id)
    {
        $name = self::PROPERTY_RIGHT_OPTION . '_' . $right_id;
        
        $this->addElement('category', $right_name);
        $this->addElement('html', '<div class="right">');
        
        $group = array();
        
        $group[] = & $this->createElement(
            'radio', 
            null, 
            null, 
            Translation::get('Everyone'), 
            self::RIGHT_OPTION_ALL, 
            array('class' => 'other_option_selected'));
        $group[] = & $this->createElement(
            'radio', 
            null, 
            null, 
            Translation::get('OnlyForMe'), 
            self::RIGHT_OPTION_ME, 
            array('class' => 'other_option_selected'));
        $group[] = & $this->createElement(
            'radio', 
            null, 
            null, 
            Translation::get('SelectSpecificEntities'), 
            self::RIGHT_OPTION_SELECT, 
            array('class' => 'entity_option_selected'));
        
        $this->addGroup($group, $name, '', '');
        
        // Add the advanced element finder
        $types = new AdvancedElementFinderElementTypes();
        
        foreach ($this->entities as $entity)
        {
            $types->add_element_type($entity->get_element_finder_type());
        }
        
        $this->addElement('html', '<div style="margin-left:25px; display:none;" class="entity_selector_box">');
        $this->addElement('advanced_element_finder', self::PROPERTY_TARGETS . '_' . $right_id, null, $types);
        
        $this->addElement('html', '</div></div>');
        
        $this->addElement('category');
    }

    /**
     * Builds the form footer
     */
    private function build_form_footer()
    {
        $buttons = array();
        
        $buttons[] = $this->createElement(
            'style_submit_button', 
            self::PROPERTY_SUBMIT, 
            Translation::get('Submit', null, Utilities::COMMON_LIBRARIES), 
            null, 
            null, 
            'arrow-right');
        
        $buttons[] = $this->createElement(
            'style_reset_button', 
            self::PROPERTY_RESET, 
            Translation::get('Reset', null, Utilities::COMMON_LIBRARIES));
        
        $this->addGroup($buttons, self::PROPERTY_BUTTONS, null, '&nbsp;', false);
        
        $this->addElement(
            'html', 
            ResourceManager::getInstance()->get_resource_html(
                Path::getInstance()->getJavascriptPath(
                    'Chamilo\Core\Repository\ContentObject\FrequentlyAskedQuestions\Display', 
                    true) . 'RightsForm.js'));
    }

    /**
     * Sets the default values for this form
     * 
     * @param string[] $defaults
     */
    public function setDefaults($defaults = array())
    {
        $locations = $this->locations;
        $first_location = $locations[0];
        
        if ($first_location)
        {
            if ($first_location->inherits())
            {
                $defaults[self::PROPERTY_INHERIT] = self::INHERIT_TRUE;
                foreach ($this->available_rights as $right_id)
                {
                    $defaults[self::PROPERTY_RIGHT_OPTION . '_' . $right_id] = self::RIGHT_OPTION_ALL;
                }
            }
            else
            {
                $defaults[self::PROPERTY_INHERIT] = self::INHERIT_FALSE;
            }
            
            $selected_entities_per_right = array();
            while ($selected_entity = $this->selected_entities->next_result())
            {
                $selected_entities_per_right[$selected_entity->get_right_id()][] = $selected_entity;
            }
            
            foreach ($this->available_rights as $right_id)
            {
                if (count($selected_entities_per_right[$right_id]) >= 1)
                {
                    $selected_entity = $selected_entities_per_right[$right_id][0];
                    if ($selected_entity->get_entity_type() == 0 && $selected_entity->get_entity_id() == 0)
                    {
                        $defaults[self::PROPERTY_RIGHT_OPTION . '_' . $right_id] = self::RIGHT_OPTION_ALL;
                        continue;
                    }
                }
                
                if (count($selected_entities_per_right[$right_id]) == 1)
                {
                    
                    if ($selected_entity->get_entity_type() == 1 &&
                         $selected_entity->get_entity_id() == Session::get_user_id())
                    {
                        $defaults[self::PROPERTY_RIGHT_OPTION . '_' . $right_id] = self::RIGHT_OPTION_ME;
                        continue;
                    }
                }
                
                $defaults[self::PROPERTY_RIGHT_OPTION . '_' . $right_id] = self::RIGHT_OPTION_SELECT;
                
                $default_elements = new AdvancedElementFinderElements();
                
                foreach ($selected_entities_per_right[$right_id] as $selected_entity)
                {
                    $entity = $this->entities[$selected_entity->get_entity_type()];
                    $default_elements->add_element(
                        $entity->get_element_finder_element($selected_entity->get_entity_id()));
                }
                
                $element = $this->getElement(self::PROPERTY_TARGETS . '_' . $right_id);
                $element->setDefaultValues($default_elements);
            }
        }
        
        parent::setDefaults($defaults);
    }
}
