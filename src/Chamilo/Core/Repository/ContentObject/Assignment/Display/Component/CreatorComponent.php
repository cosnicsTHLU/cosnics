<?php
namespace Chamilo\Core\Repository\ContentObject\Assignment\Display\Component;

use Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Storage\DataClass\Entry;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\Utilities;

/**
 *
 * @package Chamilo\Core\Repository\ContentObject\Assignment\Display\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class CreatorComponent extends Manager
{

    public function run()
    {
        $this->verifyStartEndTime();
        
        $this->set_parameter(self::PARAM_ENTITY_TYPE, $this->getEntityType());
        $this->set_parameter(self::PARAM_ENTITY_ID, $this->getEntityIdentifier());
        
        if (\Chamilo\Core\Repository\Viewer\Manager::is_ready_to_be_published())
        {
            $entry = $this->getDataProvider()->createEntry(
                $this->getEntityType(), 
                $this->getEntityIdentifier(), 
                $this->getUser()->getId(), 
                \Chamilo\Core\Repository\Viewer\Manager::get_selected_objects(), 
                $this->getRequest()->server->get('REMOTE_ADDR'));
            
            if ($entry instanceof Entry)
            {
                $this->redirect(
                    Translation::get('EntryCreated'), 
                    false, 
                    array(
                        self::PARAM_ACTION => self::ACTION_BROWSE, 
                        self::PARAM_ENTITY_TYPE => $entry->getEntityType(), 
                        self::PARAM_ENTITY_ID => $entry->getEntityId()));
            }
            else
            {
                $this->redirect(
                    Translation::get('EntryNotCreated'), 
                    true, 
                    array(self::PARAM_ACTION => self::ACTION_VIEW));
            }
        }
        else
        {
            $factory = new ApplicationFactory(
                \Chamilo\Core\Repository\Viewer\Manager::context(), 
                new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this));
            $component = $factory->getComponent();
            $component->set_maximum_select(\Chamilo\Core\Repository\Viewer\Manager::SELECT_SINGLE);
            return $component->run();
        }
    }

    protected function verifyStartEndTime()
    {
        $assignment = $this->get_root_content_object();
        
        if ($assignment->get_start_time() > time())
        {
            $date = DatetimeUtilities::format_locale_date(
                Translation::get('DateFormatShort', null, Utilities::COMMON_LIBRARIES) . ', ' .
                     Translation::get('TimeNoSecFormat', null, Utilities::COMMON_LIBRARIES), 
                    $assignment->get_start_time());
            
            $message = Translation::get('AssignmentNotStarted') . Translation::get('StartTime') . ': ' . $date;
            
            throw new \Exception($message);
        }
        
        if ($assignment->get_end_time() < time() && $assignment->get_allow_late_submissions() == 0)
        {
            $date = DatetimeUtilities::format_locale_date(
                Translation::get('DateFormatShort', null, Utilities::COMMON_LIBRARIES) . ', ' .
                     Translation::get('TimeNoSecFormat', null, Utilities::COMMON_LIBRARIES), 
                    $assignment->get_end_time());
            
            $message = Translation::get('AssignmentEnded') . Translation::get('EndTime') . ': ' . $date;
            
            throw new \Exception($message);
        }
        
        return true;
    }

    public function get_allowed_content_object_types()
    {
        return explode(',', $this->get_root_content_object()->get_allowed_types());
    }
}
