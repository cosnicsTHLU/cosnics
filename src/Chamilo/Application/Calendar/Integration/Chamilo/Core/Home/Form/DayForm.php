<?php
namespace Chamilo\Application\Calendar\Integration\Chamilo\Core\Home\Form;

use Chamilo\Application\Calendar\Integration\Chamilo\Core\Home\Type\Day;
use Chamilo\Core\Home\Form\ConfigurationForm;
use Chamilo\Libraries\Platform\Translation;

class DayForm extends ConfigurationForm
{

    /**
     *
     * @see \Chamilo\Core\Home\Form\ConfigurationForm::addSettings()
     */
    public function addSettings()
    {
        $this->add_textfield(Day::CONFIGURATION_HOUR_STEP, Translation::get('HourStep'), true);
        $this->add_textfield(Day::CONFIGURATION_TIME_START, Translation::get('TimeStart'), true);
        $this->add_textfield(Day::CONFIGURATION_TIME_END, Translation::get('TimeEnd'), true);
        $this->addElement('checkbox', Day::CONFIGURATION_TIME_HIDE, Translation::get('TimeHide'));
    }

    public function setDefaults()
    {
        $defaults = array();
        
        $defaults[Day::CONFIGURATION_HOUR_STEP] = $this->getBlock()->getSetting(Day::CONFIGURATION_HOUR_STEP, 1);
        $defaults[Day::CONFIGURATION_TIME_START] = $this->getBlock()->getSetting(Day::CONFIGURATION_TIME_START, 8);
        $defaults[Day::CONFIGURATION_TIME_END] = $this->getBlock()->getSetting(Day::CONFIGURATION_TIME_END, 17);
        $defaults[Day::CONFIGURATION_TIME_HIDE] = $this->getBlock()->getSetting(Day::CONFIGURATION_TIME_HIDE, 0);
        
        parent::setDefaults($defaults);
    }
}