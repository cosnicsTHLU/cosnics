<?php
namespace Chamilo\Core\Repository\Component;

use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Workspace\Service\RightsService;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * $Id: publication_deleter.class.php 204 2009-11-13 12:51:30Z kariboe $
 * 
 * @package repository.lib.repository_manager.component
 */
class UnlinkerComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $ids = $this->getRequest()->get(self::PARAM_CONTENT_OBJECT_ID);
        $this->set_parameter(self::PARAM_CONTENT_OBJECT_ID, $ids);
        
        if (! empty($ids))
        {
            if (! is_array($ids))
            {
                $ids = array($ids);
            }
            
            $failures = 0;
            foreach ($ids as $object_id)
            {
                $object = DataManager::retrieve_by_id(ContentObject::class_name(), $object_id);
                
                if (RightsService::getInstance()->canDestroyContentObject(
                    $this->get_user(), 
                    $object, 
                    $this->getWorkspace()))
                {
                    $versions = $object->get_content_object_versions();
                    
                    foreach ($versions as $version)
                    {
                        if (! $version->delete_links())
                        {
                            $failures ++;
                        }
                    }
                }
                else
                {
                    $failures ++;
                }
            }
            
            if ($failures)
            {
                if (count($ids) == 1)
                {
                    $message = 'ObjectNotUnlinked';
                    $parameter = array('OBJECT' => Translation::get('ContentObject'));
                }
                else
                {
                    $message = 'ObjectsNotUnlinked';
                    $parameter = array('OBJECTS' => Translation::get('ContentObjects'));
                }
            }
            else
            {
                if (count($ids) == 1)
                {
                    $message = 'ObjectUnlinked';
                    $parameter = array('OBJECT' => Translation::get('ContentObject'));
                }
                else
                {
                    $message = 'ObjectsUnlinked';
                    $parameter = array('OBJECTS' => Translation::get('ContentObjects'));
                }
            }
            
            if (count($ids) == 1)
            {
                $parameters = array(
                    Application::PARAM_ACTION => self::ACTION_VIEW_CONTENT_OBJECTS, 
                    self::PARAM_CONTENT_OBJECT_ID => $ids[0]);
            }
            else
            {
                $parameters = array(Application::PARAM_ACTION => self::ACTION_BROWSE_CONTENT_OBJECTS);
            }
            
            $this->redirect(
                Translation::get($message, $parameter, Utilities::COMMON_LIBRARIES), 
                ($failures ? true : false), 
                $parameters);
        }
        else
        {
            return $this->display_error_page(
                htmlentities(Translation::get('NoObjectSelected', null, Utilities::COMMON_LIBRARIES)));
        }
    }

    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
        $breadcrumbtrail->add(
            new Breadcrumb(
                $this->get_url(array(self::PARAM_ACTION => self::ACTION_BROWSE_CONTENT_OBJECTS)), 
                Translation::get('BrowserComponent')));
        $breadcrumbtrail->add_help('repository_unlinker');
    }
}
