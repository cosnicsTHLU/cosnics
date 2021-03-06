<?php
namespace Chamilo\Core\Repository\ContentObject\Wiki\Display\Table\WikiPage;

use Chamilo\Core\Repository\ContentObject\Wiki\Display\Manager;
use Chamilo\Core\Repository\ContentObject\WikiPage\Storage\DataClass\WikiPage;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Format\Structure\Toolbar;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Extension\DataClassTable\DataClassTableCellRenderer;
use Chamilo\Libraries\Format\Table\Interfaces\TableCellRendererActionsColumnSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\StringUtilities;
use Chamilo\Libraries\Utilities\Utilities;

class WikiPageTableCellRenderer extends DataClassTableCellRenderer implements TableCellRendererActionsColumnSupport
{

    /**
     *
     * @var WikiPage
     */
    protected $wikiPage;

    /**
     *
     * @var int
     */
    protected $complex_id;

    public function render_cell($column, $publication)
    {
        $this->publication_id = Request::get('publication_id');
        
        $this->wikiPage = $this->get_publication_from_complex_content_object_item($publication);
        $this->complex_id = $publication->get_id();
        
        if ($publication->get_additional_property('is_homepage') == 1)
        {
            $homepage = ' (' . Translation::get('Homepage') . ')';
        }
        
        if (isset($this->wikiPage))
        {
            if ($property = $column->get_name())
            {
                switch ($property)
                {
                    case ContentObject::PROPERTY_TITLE :
                        return '<a href="' . $this->get_component()->get_url(
                            array(
                                Manager::PARAM_ACTION => Manager::ACTION_VIEW_WIKI_PAGE, 
                                Manager::PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $this->complex_id)) . '">' .
                             htmlspecialchars($this->wikiPage->get_title()) . '</a>' . $homepage;
                    case Translation::get('Versions') :
                        return $this->wikiPage->get_version_count();
                    case ContentObject::PROPERTY_DESCRIPTION :
                        $description = str_ireplace(
                            ']]', 
                            '', 
                            str_ireplace('[[', '', str_ireplace('=', '', $this->wikiPage->get_description())));
                        
                        return StringUtilities::getInstance()->truncate($description, 50);
                    case ContentObject::PROPERTY_MODIFICATION_DATE :
                        return DatetimeUtilities::format_locale_date(null, $this->wikiPage->get_modification_date());
                }
            }
            
            return parent::render_cell($column, $this->wikiPage);
        }
    }

    public function get_actions($publication)
    {
        $isOwner = $this->wikiPage->get_owner_id() == $this->get_component()->getUser()->getId();
        
        $toolbar = new Toolbar();
        if ($this->get_component()->get_parent()->is_allowed_to_delete_child())
        {
            $toolbar->add_item(
                new ToolbarItem(
                    Translation::get('Delete', null, Utilities::COMMON_LIBRARIES), 
                    Theme::getInstance()->getCommonImagePath('Action/Delete'), 
                    $this->get_component()->get_url(
                        array(
                            Manager::PARAM_ACTION => Manager::ACTION_DELETE_COMPLEX_CONTENT_OBJECT_ITEM, 
                            Manager::PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $this->complex_id)), 
                    ToolbarItem::DISPLAY_ICON, 
                    true));
        }
        
        if ($this->get_component()->get_parent()->is_allowed_to_edit_content_object() || $isOwner)
        {
            $toolbar->add_item(
                new ToolbarItem(
                    Translation::get('Edit', null, Utilities::COMMON_LIBRARIES), 
                    Theme::getInstance()->getCommonImagePath('Action/Edit'), 
                    $this->get_component()->get_url(
                        array(
                            Manager::PARAM_ACTION => Manager::ACTION_UPDATE_COMPLEX_CONTENT_OBJECT_ITEM, 
                            Manager::PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $this->complex_id)), 
                    ToolbarItem::DISPLAY_ICON));
        }
        
        if ($this->get_component()->get_parent()->is_allowed_to_edit_content_object())
        {
            if (($publication->get_additional_property('is_homepage') == 0))
            {
                $toolbar->add_item(
                    new ToolbarItem(
                        Translation::get('SetAsHomepage'), 
                        Theme::getInstance()->getCommonImagePath('Action/Home'), 
                        $this->get_component()->get_url(
                            array(
                                Manager::PARAM_ACTION => Manager::ACTION_SET_AS_HOMEPAGE, 
                                Manager::PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $this->complex_id)), 
                        ToolbarItem::DISPLAY_ICON));
            }
            else
            {
                $toolbar->add_item(
                    new ToolbarItem(
                        Translation::get('SetAsHomepage'), 
                        Theme::getInstance()->getCommonImagePath('Action/HomeNa'), 
                        null, 
                        ToolbarItem::DISPLAY_ICON));
            }
        }
        
        return $toolbar->as_html();
    }

    /*
     * private function get_publish_links($wiki_page) { }
     */
    private function get_publication_from_complex_content_object_item($clo_item)
    {
        return \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
            ContentObject::class_name(), 
            $clo_item->get_ref());
    }
}
