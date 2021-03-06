<?php
namespace Chamilo\Core\Repository\Builder\Action\Component;

use Chamilo\Core\Repository\Builder\Action\Manager;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\Utilities;

/**
 * $Id: mover.class.php 200 2009-11-13 12:30:04Z kariboe $
 * 
 * @package repository.lib.complex_builder.component
 */
/**
 * Repository manager component which provides functionality to delete an object from the users repository.
 */
class MoverComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $id = Request::get(\Chamilo\Core\Repository\Builder\Manager::PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID);
        $parent_complex_content_object_item = Request::get(
            \Chamilo\Core\Repository\Builder\Manager::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID);
        $direction = Request::get(\Chamilo\Core\Repository\Builder\Manager::PARAM_DIRECTION);
        $succes = true;
        
        if (isset($id))
        {
            $complex_content_object_item = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                ComplexContentObjectItem::class_name(), 
                $id);
            $parent = $complex_content_object_item->get_parent();
            $max = \Chamilo\Core\Repository\Storage\DataManager::count_complex_content_object_items(
                \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::class_name(), 
                new EqualityCondition(
                    new PropertyConditionVariable(
                        \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::class_name(), 
                        \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::PROPERTY_PARENT), 
                    new StaticConditionVariable($parent)));
            
            $display_order = $complex_content_object_item->get_display_order();
            $new_place = ($display_order +
                 ($direction == \Chamilo\Core\Repository\Manager::PARAM_DIRECTION_UP ? - 1 : 1));
            
            if ($new_place > 0 && $new_place <= $max)
            {
                $complex_content_object_item->set_display_order($new_place);
                
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::class_name(), 
                        \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::PROPERTY_DISPLAY_ORDER), 
                    new StaticConditionVariable($new_place), 
                    \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::get_table_name());
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::class_name(), 
                        \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::PROPERTY_PARENT), 
                    new StaticConditionVariable($parent), 
                    \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::get_table_name());
                $condition = new AndCondition($conditions);
                $items = \Chamilo\Core\Repository\Storage\DataManager::retrieve_complex_content_object_items(
                    \Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem::class_name(), 
                    $condition);
                $new_complex_content_object_item = $items->next_result();
                $new_complex_content_object_item->set_display_order($display_order);
                
                if (! $complex_content_object_item->update() || ! $new_complex_content_object_item->update())
                {
                    $succes = false;
                }
            }
            
            $this->redirect(
                $succes ? Translation::get(
                    'ObjectsMoved', 
                    array('OBJECTS' => Translation::get('ComplexContentObjectItems')), 
                    Utilities::COMMON_LIBRARIES) : Translation::get(
                    'ObjectsNotMoved', 
                    array('OBJECTS' => Translation::get('ComplexContentObjectItems')), 
                    Utilities::COMMON_LIBRARIES), 
                false, 
                array(
                    \Chamilo\Core\Repository\Builder\Manager::PARAM_ACTION => \Chamilo\Core\Repository\Builder\Manager::ACTION_BROWSE, 
                    \Chamilo\Core\Repository\Builder\Manager::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID => $parent_complex_content_object_item));
        }
        else
        {
            return $this->display_error_page(
                htmlentities(
                    Translation::get(
                        'NoObjectsSelected', 
                        array('OBJECTS' => Translation::get('ContentObjectItems')), 
                        Utilities::COMMON_LIBRARIES)));
        }
    }
}
