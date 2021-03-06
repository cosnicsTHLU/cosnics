<?php
namespace Chamilo\Application\Weblcms\Renderer\PublicationList\Type;

use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * $Id: content_object_publication_details_renderer.class.php 216 2009-11-13
 * 14:08:06Z kariboe $
 * 
 * @package application.lib.weblcms.browser.list_renderer
 */
/**
 * Renderer to display all details of learning object publication
 */
class ContentObjectPublicationDetailsRenderer extends ListContentObjectPublicationListRenderer
{

    /**
     *
     * @see \Chamilo\Application\Weblcms\Renderer\PublicationList\ContentObjectPublicationListRenderer::get_publications()
     */
    public function get_publications()
    {
        $publication_id = $this->get_tool_browser()->get_publication_id();
        
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class_name(), 
                ContentObjectPublication::PROPERTY_ID), 
            new StaticConditionVariable($publication_id));
        
        return DataManager::retrieve_content_object_publications($condition, array(), 0, 1)->as_array();
    }

    /**
     *
     * @see \Chamilo\Application\Weblcms\Renderer\PublicationList\ContentObjectPublicationListRenderer::as_html()
     */
    public function as_html()
    {
        $this->get_tool_browser()->get_parent()->set_parameter(
            \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID, 
            $this->get_tool_browser()->get_publication_id());
        
        return parent::as_html();
    }
}
