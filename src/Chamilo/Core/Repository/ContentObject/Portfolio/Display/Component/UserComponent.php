<?php
namespace Chamilo\Core\Repository\ContentObject\Portfolio\Display\Component;

use Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights;
use Chamilo\Core\Repository\ContentObject\Portfolio\Display\Table\User\UserTable;
use Chamilo\Libraries\Format\Display;
use Chamilo\Libraries\Format\Structure\ActionBar\ActionBarSearchForm;
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
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\NotCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * Component that allows a user to emulate the rights another user has on his or her portfolio
 * 
 * @package repository\content_object\portfolio\display
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class UserComponent extends ItemComponent implements TableSupport
{

    /**
     *
     * @var ButtonToolBarRenderer
     */
    private $buttonToolbarRenderer;

    /**
     * Executes this component
     */
    public function build()
    {
        
        // Check whether portfolio rights are enabled and whether the user can actually set them
        if (! $this->get_parent() instanceof PortfolioComplexRights ||
             ! $this->get_parent()->is_allowed_to_set_content_object_rights())
        {
            $message = Display::warning_message(Translation::get('ComplexRightsNotSupported'));
            
            $html = array();
            
            $html[] = $this->render_header();
            $html[] = $message;
            $html[] = $this->render_footer();
            
            return implode(PHP_EOL, $html);
        }
        
        // If a virtual user is currently configured, clear it
        $virtual_user = $this->get_parent()->get_portfolio_virtual_user();
        
        if ($virtual_user instanceof \Chamilo\Core\User\Storage\DataClass\User)
        {
            $this->get_parent()->clear_virtual_user_id();
            $this->redirect(
                Translation::get('BackInRegularView'), 
                false, 
                array(self::PARAM_ACTION => self::ACTION_VIEW_COMPLEX_CONTENT_OBJECT));
        }
        
        $this->buttonToolbarRenderer = $this->getButtonToolbarRenderer();
        
        $this->set_parameter(
            ActionBarSearchForm::PARAM_SIMPLE_SEARCH_QUERY, 
            $this->buttonToolbarRenderer->getSearchForm()->getQuery());
        
        // Handle a virtual user selection
        $selected_virtual_user_id = Request::get(self::PARAM_VIRTUAL_USER_ID);
        
        if ($selected_virtual_user_id)
        {
            if (! $this->get_parent()->set_portfolio_virtual_user_id($selected_virtual_user_id))
            {
                $this->redirect(Translation::get('ImpossibleToViewAsSelectedUser'), true);
            }
            else
            {
                $this->redirect(
                    Translation::get('ViewingPortfolioAsSelectedUser'), 
                    false, 
                    array(self::PARAM_ACTION => self::ACTION_VIEW_COMPLEX_CONTENT_OBJECT));
            }
        }
        
        // Default table of users which can be emulated (as determined by the context)
        $table = new UserTable($this);
        
        $html = array();
        $html[] = $this->buttonToolbarRenderer->render();
        $html[] = $table->as_html();
        
        $axtionBar = implode(PHP_EOL, $html);
        
        $html = array();
        
        $html[] = $this->render_header();
        $html[] = $axtionBar;
        $html[] = $this->render_footer();
        
        return implode(PHP_EOL, $html);
    }

    /**
     * Returns the condition
     * 
     * @param string $table_class_name
     * @return \libraries\storage\Condition
     */
    public function get_table_condition($table_class_name)
    {
        $properties = array();
        $properties[] = new PropertyConditionVariable(
            \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
            \Chamilo\Core\User\Storage\DataClass\User::PROPERTY_FIRSTNAME);
        $properties[] = new PropertyConditionVariable(
            \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
            \Chamilo\Core\User\Storage\DataClass\User::PROPERTY_LASTNAME);
        $properties[] = new PropertyConditionVariable(
            \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
            \Chamilo\Core\User\Storage\DataClass\User::PROPERTY_OFFICIAL_CODE);
        
        $searchConditions = $this->buttonToolbarRenderer->getConditions($properties);
        
        $conditions = array();
        
        if ($searchConditions instanceof Condition)
        {
            $conditions[] = $searchConditions;
        }
        
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
                \Chamilo\Core\User\Storage\DataClass\User::PROPERTY_PLATFORMADMIN), 
            new StaticConditionVariable(0));
        $conditions[] = new NotCondition(
            new EqualityCondition(
                new PropertyConditionVariable(
                    \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
                    \Chamilo\Core\User\Storage\DataClass\User::PROPERTY_OFFICIAL_CODE), 
                new StaticConditionVariable('')));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
                \Chamilo\Core\User\Storage\DataClass\User::PROPERTY_ACTIVE), 
            new StaticConditionVariable(1));
        
        return new AndCondition($conditions);
    }

    /**
     * Get the component actionbar
     * 
     * @return ButtonToolBarRenderer
     */
    public function getButtonToolbarRenderer()
    {
        if (! isset($this->buttonToolbarRenderer))
        {
            $buttonToolbar = new ButtonToolBar($this->get_url());
            $commonActions = new ButtonGroup();
            
            $commonActions->addButton(
                new Button(
                    Translation::get('ShowAll', null, Utilities::COMMON_LIBRARIES), 
                    Theme::getInstance()->getCommonImagePath('Action/Browser'), 
                    $this->get_url(), 
                    ToolbarItem::DISPLAY_ICON_AND_LABEL));
            
            $buttonToolbar->addButtonGroup($commonActions);
            $this->buttonToolbarRenderer = new ButtonToolBarRenderer($buttonToolbar);
        }
        
        return $this->buttonToolbarRenderer;
    }
}
