<?php
namespace Chamilo\Core\Repository\Builder\Action\Component;

use Chamilo\Core\Repository\Builder\Action\Manager;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * $Id: deleter.class.php 200 2009-11-13 12:30:04Z kariboe $
 * 
 * @package repository.lib.complex_builder.component
 */
/**
 */
class ViewerComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $id = Request::get(\Chamilo\Core\Repository\Builder\Manager::PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID);
        
        if ($id)
        {
            $complex_content_object_item = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                ComplexContentObjectItem::class_name(), 
                $id);
            $content_object = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                ContentObject::class_name(), 
                $complex_content_object_item->get_ref());
            if (\Chamilo\Core\Repository\Storage\DataManager::is_helper_type($content_object->get_type()))
            {
                $content_object = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                    ContentObject::class_name(), 
                    $content_object->get_additional_property('reference_id'));
            }
            
            $trail = BreadcrumbTrail::getInstance();
            $this->get_complex_content_object_breadcrumbs();
            $parameters = array(
                \Chamilo\Core\Repository\Builder\Manager::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID => $this->get_parent()->get_complex_content_object_item_id(), 
                \Chamilo\Core\Repository\Builder\Manager::PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $id);
            $trail->add(
                new Breadcrumb(
                    $this->get_url($parameters), 
                    Translation::get('View', null, Utilities::COMMON_LIBRARIES) . ' ' . $content_object->get_title()));
            
            $html = array();
            
            $html[] = $this->render_header();
            $html[] = ContentObjectRenditionImplementation::launch(
                $content_object, 
                ContentObjectRendition::FORMAT_HTML, 
                ContentObjectRendition::VIEW_FULL, 
                $this);
            $html[] = $this->render_footer();
            
            return implode(PHP_EOL, $html);
        }
        else
        {
            return $this->display_error_page(
                htmlentities(
                    Translation::get(
                        'NoObjectSelected', 
                        array('OBJECT' => Translation::get('ContentObject')), 
                        Utilities::COMMON_LIBRARIES)));
        }
    }
}
