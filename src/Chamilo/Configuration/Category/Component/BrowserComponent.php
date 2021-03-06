<?php
namespace Chamilo\Configuration\Category\Component;

use Chamilo\Configuration\Category\Manager;
use Chamilo\Configuration\Category\Menu\CategoryMenu;
use Chamilo\Configuration\Category\Storage\DataClass\PlatformCategory;
use Chamilo\Configuration\Category\Table\Browser\CategoryTable;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Interfaces\TableSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Condition\PatternMatchCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * $Id: browser.class.php 205 2009-11-13 12:57:33Z vanpouckesven $
 * 
 * @package application.common.category_manager.component
 */
class BrowserComponent extends Manager implements TableSupport
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
        $this->buttonToolbarRenderer = $this->getButtonToolbarRenderer();
        $category_id = Request::get(self::PARAM_CATEGORY_ID);
        $menu = new CategoryMenu($category_id, $this->get_parent());
        
        $this->set_parameter(self::PARAM_CATEGORY_ID, $category_id);
        
        $html = array();
        
        $html[] = $this->render_header();
        $html[] = $this->buttonToolbarRenderer->render() . '<br />';
        
        if ($this->get_subcategories_allowed())
        {
            $html[] = '<div style="float: left; padding-right: 20px; width: 18%; overflow: auto; height: 100%;">' .
                 $menu->render_as_tree() . '</div>';
        }
        
        $html[] = $this->get_user_html();
        $html[] = $this->render_footer();
        
        return implode(PHP_EOL, $html);
    }

    public function get_user_html()
    {
        $parameters = array_merge(
            $this->get_parameters(), 
            array(
                self::PARAM_ACTION => self::ACTION_BROWSE_CATEGORIES, 
                self::PARAM_CATEGORY_ID => $this->get_category_id()));
        $table = new CategoryTable($this);
        $html = array();
        
        if ($this->get_subcategories_allowed())
        {
            $html[] = '<div style="float: right; width: 80%;">';
            $html[] = $table->as_html();
            $html[] = '</div>';
        }
        else
        {
            $html[] = $table->as_html();
        }
        
        return implode($html, "\n");
    }

    public function get_condition()
    {
        $category_class_name = get_class($this->get_parent()->get_category());
        $class_name = $category_class_name::class_name();
        
        $cat_id = $this->get_category_id();
        
        $condition = new EqualityCondition(
            new PropertyConditionVariable($class_name, PlatformCategory::PROPERTY_PARENT), 
            new StaticConditionVariable($cat_id));
        
        $search = $this->buttonToolbarRenderer->getSearchForm()->getQuery();
        if (isset($search) && ($search != ''))
        {
            $conditions = array();
            $conditions[] = new PatternMatchCondition(
                new PropertyConditionVariable($class_name, PlatformCategory::PROPERTY_NAME), 
                '*' . $search . '*');
            $orcondition = new OrCondition($conditions);
            
            $conditions = array();
            $conditions[] = $orcondition;
            $conditions[] = $condition;
            $condition = new AndCondition($conditions);
        }
        return $condition;
    }

    public function get_category()
    {
        return (Request::get(self::PARAM_CATEGORY_ID) ? Request::get(self::PARAM_CATEGORY_ID) : 0);
    }

    public function get_category_id()
    {
        return (Request::get(self::PARAM_CATEGORY_ID) ? Request::get(self::PARAM_CATEGORY_ID) : 0);
    }

    public function getButtonToolbarRenderer()
    {
        if (! isset($this->buttonToolbarRenderer))
        {
            $buttonToolbar = new ButtonToolBar(
                $this->get_url(array(self::PARAM_CATEGORY_ID => $this->get_category_id())));
            $commonActions = new ButtonGroup();
            
            if ($this->get_parent()->allowed_to_add_category($this->get_category_id()))
            {
                $commonActions->addButton(
                    new Button(
                        Translation::get('Add', null, Utilities::COMMON_LIBRARIES), 
                        Theme::getInstance()->getCommonImagePath('Action/Add'), 
                        $this->get_create_category_url(Request::get(self::PARAM_CATEGORY_ID)), 
                        ToolbarItem::DISPLAY_ICON_AND_LABEL));
            }
            
            $buttonToolbar->addButtonGroup($commonActions);
            
            $this->buttonToolbarRenderer = new ButtonToolBarRenderer($buttonToolbar);
        }
        
        return $this->buttonToolbarRenderer;
    }

    /*
     * (non-PHPdoc) @see \libraries\format\TableSupport::get_table_condition()
     */
    public function get_table_condition($table_class_name)
    {
        return $this->get_condition();
    }
}
