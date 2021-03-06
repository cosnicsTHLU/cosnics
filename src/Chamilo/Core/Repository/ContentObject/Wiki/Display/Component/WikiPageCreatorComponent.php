<?php
namespace Chamilo\Core\Repository\ContentObject\Wiki\Display\Component;

use Chamilo\Core\Repository\ContentObject\Wiki\Display\Manager;
use Chamilo\Core\Repository\ContentObject\WikiPage\Storage\DataClass\ComplexWikiPage;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;

/**
 * $Id: wiki_page_creator.class.php 205 2009-11-13 12:57:33Z vanpouckesven $
 * 
 * @package repository.lib.complex_display.wiki.component
 */
/*
 * This is the compenent that allows the user to create a wiki_page. Author: Stefan Billiet Author: Nick De Feyter
 */
class WikiPageCreatorComponent extends Manager implements \Chamilo\Core\Repository\Viewer\ViewerInterface, 
    DelegateComponent
{

    public function run()
    {
        if (! \Chamilo\Core\Repository\Viewer\Manager::is_ready_to_be_published())
        {
            $factory = new ApplicationFactory(
                \Chamilo\Core\Repository\Viewer\Manager::context(), 
                new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this));
            $component = $factory->getComponent();
            $component->set_parameter(self::PARAM_ACTION, self::ACTION_CREATE_PAGE);
            return $component->run();
        }
        else
        {
            $objects = \Chamilo\Core\Repository\Viewer\Manager::get_selected_objects();
            
            if (! is_array($objects))
            {
                $objects = array($objects);
            }
            
            foreach ($objects as $object)
            {
                $complex_content_object_item = ComplexContentObjectItem::factory(ComplexWikiPage::class_name());
                $complex_content_object_item->set_ref($object);
                $complex_content_object_item->set_parent($this->get_root_content_object()->get_id());
                $complex_content_object_item->set_user_id($this->get_user_id());
                $complex_content_object_item->set_display_order(
                    \Chamilo\Core\Repository\Storage\DataManager::select_next_display_order(
                        $this->get_root_content_object()->get_id()));
                $complex_content_object_item->set_is_homepage(0);
                $complex_content_object_item->create();
            }
            
            $this->redirect(
                Translation::get('WikiItemCreated'), 
                '', 
                array(
                    self::PARAM_ACTION => self::ACTION_VIEW_WIKI_PAGE, 
                    self::PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $complex_content_object_item->get_id()));
        }
    }

    public function render_header()
    {
        $html = array();
        
        $html[] = parent::render_header();
        
        $repository_viewer_action = Request::get(\Chamilo\Core\Repository\Viewer\Manager::PARAM_ACTION);
        
        switch ($repository_viewer_action)
        {
            case \Chamilo\Core\Repository\Viewer\Manager::ACTION_BROWSER :
                $title = 'BrowseAvailableWikiPages';
                break;
            case \Chamilo\Core\Repository\Viewer\Manager::ACTION_CREATOR :
                $title = 'CreateWikiPage';
                break;
            case \Chamilo\Core\Repository\Viewer\Manager::ACTION_VIEWER :
                $title = 'PreviewWikiPage';
                break;
            default :
                $title = 'CreateWikiPage';
                break;
        }
        
        $html[] = '<div class="wiki-pane-content-title">' . Translation::get($title) . '</div>';
        $html[] = '<div class="wiki-pane-content-subtitle">' . Translation::get('In') . ' ' .
             $this->get_root_content_object()->get_title() . '</div>';
        
        return implode(PHP_EOL, $html);
    }

    public function get_allowed_content_object_types()
    {
        return $this->get_root_content_object()->get_allowed_types();
    }
}
