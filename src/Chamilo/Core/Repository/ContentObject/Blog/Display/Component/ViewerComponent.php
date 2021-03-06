<?php
namespace Chamilo\Core\Repository\ContentObject\Blog\Display\Component;

use Chamilo\Core\Repository\ContentObject\Blog\Display\Component\Viewer\BlogLayout;
use Chamilo\Core\Repository\ContentObject\Blog\Display\Manager;
use Chamilo\Core\Repository\ContentObject\BlogItem\Storage\DataClass\BlogItem;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * $Id: blog_viewer.class.php 200 2009-11-13 12:30:04Z kariboe $
 * 
 * @package repository.lib.complex_display.blog.component
 */
class ViewerComponent extends Manager implements DelegateComponent
{

    /**
     *
     * @var ButtonToolBarRenderer
     */
    private $buttonToolbarRenderer;

    public function run()
    {
        $blog = $this->get_root_content_object();
        $trail = BreadcrumbTrail::getInstance();
        $blog_layout = BlogLayout::factory($this, $blog);
        $this->buttonToolbarRenderer = $this->getButtonToolbarRenderer();
        $html = array();
        
        $html[] = $this->render_header();
        $html[] = $this->buttonToolbarRenderer->render();
        $html[] = $blog_layout->as_html();
        $html[] = $this->render_footer();
        
        return implode(PHP_EOL, $html);
    }

    public function getButtonToolbarRenderer()
    {
        if (! isset($this->buttonToolbarRenderer))
        {
            $buttonToolbar = new ButtonToolBar();
            $commonActions = new ButtonGroup();
            
            if ($this->get_parent()->is_allowed_to_add_child())
            {
                $commonActions->addButton(
                    new Button(
                        Translation::get('CreateItem', null, Utilities::COMMON_LIBRARIES), 
                        Theme::getInstance()->getCommonImagePath('Action/Create'), 
                        $this->get_url(
                            array(
                                self::PARAM_ACTION => self::ACTION_CREATE_COMPLEX_CONTENT_OBJECT_ITEM, 
                                self::PARAM_TYPE => BlogItem::class_name())), 
                        ToolbarItem::DISPLAY_ICON_AND_LABEL));
            }
            
            $buttonToolbar->addButtonGroup($commonActions);
            $this->buttonToolbarRenderer = new ButtonToolBarRenderer($buttonToolbar);
        }
        
        return $this->buttonToolbarRenderer;
    }
}
