<?php
namespace Chamilo\Libraries\Format\Form;

use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

class FormValidatorHtmlEditor
{

    private $form;

    private $name;

    private $label;

    private $required;

    private $attributes;

    private $options;
    const SETTING_TOOLBAR = 'toolbar';
    const SETTING_LANGUAGE = 'language';
    const SETTING_THEME = 'theme';
    const SETTING_WIDTH = 'width';
    const SETTING_HEIGHT = 'height';
    const SETTING_COLLAPSE_TOOLBAR = 'collapse_toolbar';
    const SETTING_CONFIGURATION = 'configuration';
    const SETTING_FULL_PAGE = 'full_page';
    const SETTING_ENTER_MODE = 'enter_mode';
    const SETTING_SHIFT_ENTER_MODE = 'shift_enter_mode';
    const SETTING_TEMPLATES = 'templates';

    public function __construct($name, $label, $required = true, $options = array(), $attributes = array())
    {
        $this->name = $name;
        $this->label = $label;
        $this->required = $required;
        $this->options = $options;
        
        if (! array_key_exists('class', $attributes))
        {
            $attributes['class'] = 'html_editor';
        }
        
        $this->attributes = $attributes;
    }

    public function add()
    {
        $form = $this->get_form();
        $element = $this->create();
        
        $form->addElement($element);
        $form->applyFilter($this->get_name(), 'trim');
        
        if ($this->get_required())
        {
            $form->addRule($this->get_name(), Translation::get('ThisFieldIsRequired'), 'required');
        }
    }

    public function create()
    {
        $form = $this->get_form();
        $form->register_html_editor($this->name);
        return $form->createElement('textarea', $this->name, $this->label, $this->attributes);
    }

    public function render()
    {
        return FormValidator::createElement('textarea', $this->name, $this->label, $this->attributes)->toHtml();
    }

    public function get_form()
    {
        return $this->form;
    }

    public function set_form($form)
    {
        $this->form = $form;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function set_name($name)
    {
        $this->name = $name;
    }

    public function get_label()
    {
        return $this->label;
    }

    public function set_label($label)
    {
        $this->label = $label;
    }

    public function get_attributes()
    {
        return $this->attributes;
    }

    public function set_attributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function get_options()
    {
        return $this->options;
    }

    public function set_options($options)
    {
        $this->options = $options;
    }

    public function get_option($variable)
    {
        if (isset($this->options[$variable]))
        {
            return $this->options[$variable];
        }
        else
        {
            return null;
        }
    }

    public function set_option($variable, $value)
    {
        $this->options[$variable] = $value;
    }

    public function get_required()
    {
        return $this->required;
    }

    public function set_required($required)
    {
        $this->required = $required;
    }

    public static function factory($type, $name, $label, $required = true, $options = array(), $attributes = array())
    {
        $class = __NAMESPACE__ . '\\' . 'FormValidator' .
             StringUtilities::getInstance()->createString($type)->upperCamelize() . 'HtmlEditor';
        
        if (class_exists($class))
        {
            $options = FormValidatorHtmlEditorOptions::factory($type, $options);
            
            if ($options)
            {
                return new $class($name, $label, $required, $options, $attributes);
            }
        }
    }
}
