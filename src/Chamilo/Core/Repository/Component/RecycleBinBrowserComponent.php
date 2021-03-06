<?php
namespace Chamilo\Core\Repository\Component;

use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Core\Repository\Table\ContentObject\RecycleBin\RecycleBinTable;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Interfaces\TableSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Cache\DataClassCountCache;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;

/**
 * $Id: recycle_bin_browser.class.php 204 2009-11-13 12:51:30Z kariboe $
 * 
 * @package repository.lib.repository_manager.component
 */
class RecycleBinBrowserComponent extends Manager implements TableSupport
{

    /**
     *
     * @var ButtonToolBarRenderer
     */
    private $buttonToolbarRenderer;

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $trail = BreadcrumbTrail::getInstance();
        $trail->add(new Breadcrumb($this->get_url(), Translation::get('RecycleBin')));
        $trail->add_help('repository recyclebin');
        
        $this->buttonToolbarRenderer = $this->getButtonToolbarRenderer();
        
        $html = array();
        
        $html[] = $this->render_header();
        
        if (Request::get(self::PARAM_EMPTY_RECYCLE_BIN))
        {
            $this->empty_recycle_bin();
            $html[] = $this->display_message(htmlentities(Translation::get('RecycleBinEmptied')));
        }
        
        $html[] = $this->buttonToolbarRenderer->render();
        $html[] = $this->display_content_objects();
        $html[] = $this->render_footer();
        
        return implode(PHP_EOL, $html);
    }

    /**
     * Display content objects in the recycle bin.
     * 
     * @return int The number of content objects currently in the recycle bin.
     */
    private function display_content_objects()
    {
        $table = new RecycleBinTable($this);
        
        return $table->as_html();
    }

    /**
     * Empty the recycle bin.
     * This function will permanently delete all objects from the recycle bin. Only objects from
     * current user will be deleted.
     */
    private function empty_recycle_bin()
    {
        $parameters = new DataClassRetrievesParameters($this->get_current_user_recycle_bin_conditions());
        $trashed_objects = DataManager::retrieve_active_content_objects(ContentObject::class_name(), $parameters);
        $count = 0;
        while ($object = $trashed_objects->next_result())
        {
            $object->delete();
            $count ++;
        }
        
        DataClassCountCache::truncate(ContentObject::class_name());
        
        return $count;
    }

    public function getButtonToolbarRenderer()
    {
        if (! isset($this->buttonToolbarRenderer))
        {
            $buttonToolbar = new ButtonToolBar();
            $commonActions = new ButtonGroup();
            
            $commonActions->addButton(
                new Button(
                    Translation::get('EmptyRecycleBin'), 
                    Theme::getInstance()->getCommonImagePath('Treemenu/Trash'), 
                    $this->get_url(array(self::PARAM_EMPTY_RECYCLE_BIN => 1)), 
                    ToolbarItem::DISPLAY_ICON_AND_LABEL, 
                    Translation::get('ConfirmEmptyRecycleBin')));
            
            $buttonToolbar->addButtonGroup($commonActions);
            
            $this->buttonToolbarRenderer = new ButtonToolBarRenderer($buttonToolbar);
        }
        
        return $this->buttonToolbarRenderer;
    }

    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
        $breadcrumbtrail->add(
            new Breadcrumb(
                $this->get_url(array(self::PARAM_ACTION => self::ACTION_BROWSE_CONTENT_OBJECTS)), 
                Translation::get('RepositoryManagerBrowserComponent')));
        $breadcrumbtrail->add_help('repository_recycle_bin_browser');
    }

    /*
     * (non-PHPdoc) @see \libraries\format\TableSupport::get_table_condition()
     */
    public function get_table_condition($table_class_name)
    {
        return $this->get_current_user_recycle_bin_conditions();
    }
}
