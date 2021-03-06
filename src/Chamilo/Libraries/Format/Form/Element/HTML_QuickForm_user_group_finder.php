<?php
/**
 *
 * @package common.html.formvalidator.Element
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'HTML_QuickForm_element_finder.php';

/**
 * AJAX-based tree search and multiselect element.
 * Use at your own risk.
 * 
 * @author Tim De Pauw
 */
class HTML_QuickForm_user_group_finder extends HTML_QuickForm_element_finder
{

    public function __construct($elementName, $elementLabel, $search_url, $locale = array ('Display' => 'Display'), $default_values = array (), 
        $options = array())
    {
        parent::__construct($elementName, $elementLabel, $search_url, $locale, $default_values, $options);
        $this->_type = 'user_group_finder';
    }

    public function getValue()
    {
        $results = array();
        $values = $this->get_active_elements();
        
        /**
         * Process the array values so we end up with a 2-dimensional array
         * Keys are the selection type, values are the selected objects
         */
        
        foreach ($values as $value)
        {
            $value = explode('_', $value['id']);
            
            if (! isset($results[$value[0]]) || ! is_array($results[$value[0]]))
            {
                $results[$value[0]] = array();
            }
            
            $results[$value[0]][] = $value[1];
        }
        
        return $results;
    }
}
