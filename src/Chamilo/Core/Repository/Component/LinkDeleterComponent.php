<?php
namespace Chamilo\Core\Repository\Component;

use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Core\Repository\Table\Link\LinkTable;
use Chamilo\Core\Repository\Workspace\Service\RightsService;
use Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 *
 * @package Chamilo\Core\Repository\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class LinkDeleterComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $linkType = Request::get(self::PARAM_LINK_TYPE);
        $contentObjectIdentifier = Request::get(self::PARAM_CONTENT_OBJECT_ID);
        $linkIdentifiers = Request::get(self::PARAM_LINK_ID);
        
        $this->set_parameter(self::PARAM_LINK_TYPE, $linkType);
        $this->set_parameter(self::PARAM_CONTENT_OBJECT_ID, $contentObjectIdentifier);
        $this->set_parameter(self::PARAM_LINK_ID, $linkIdentifiers);
        
        if (! $contentObjectIdentifier)
        {
            throw new NoObjectSelectedException(Translation::get('ContentObject'));
        }
        
        $contentObject = DataManager::retrieve_by_id(ContentObject::class_name(), $contentObjectIdentifier);
        
        if (! RightsService::getInstance()->canDestroyContentObject(
            $this->get_user(), 
            $contentObject, 
            $this->getWorkspace()))
        {
            throw new NotAllowedException();
        }
        
        if (! is_array($linkIdentifiers))
        {
            $linkIdentifiers = array($linkIdentifiers);
        }
        
        if ($linkType && $contentObjectIdentifier && count($linkIdentifiers) > 0)
        {
            switch ($linkType)
            {
                case LinkTable::TYPE_PUBLICATIONS :
                    list($message, $is_error_message) = $this->delete_publication(
                        $contentObjectIdentifier, 
                        $linkIdentifiers);
                    break;
                case LinkTable::TYPE_PARENTS :
                    list($message, $is_error_message) = $this->delete_complex_wrapper($linkIdentifiers);
                    break;
                case LinkTable::TYPE_CHILDREN :
                    list($message, $is_error_message) = $this->delete_complex_wrapper($linkIdentifiers);
                    break;
                case LinkTable::TYPE_ATTACHED_TO :
                    list($message, $is_error_message) = $this->delete_attacher(
                        $contentObjectIdentifier, 
                        $linkIdentifiers);
                    break;
                case LinkTable::TYPE_ATTACHES :
                    list($message, $is_error_message) = $this->delete_attachment(
                        $contentObjectIdentifier, 
                        $linkIdentifiers);
                    break;
            }
            
            $this->redirect(
                $message, 
                $is_error_message, 
                array(
                    self::PARAM_ACTION => self::ACTION_VIEW_CONTENT_OBJECTS, 
                    self::PARAM_CONTENT_OBJECT_ID => $contentObjectIdentifier));
        }
        else
        {
            return $this->display_error_page(
                Translation::get(
                    'NoObjectSelected', 
                    array('OBJECT' => Translation::get('ContentObject')), 
                    Utilities::COMMON_LIBRARIES));
        }
    }

    public function delete_publication($object_id, $link_ids)
    {
        $failures = 0;
        
        foreach ($link_ids as $link_id)
        {
            list($application, $publication_id) = explode("|", $link_id);
            if (! \Chamilo\Core\Repository\Publication\Storage\DataManager\DataManager::delete_content_object_publication(
                $application, 
                $publication_id))
            {
                $failures ++;
            }
        }
        
        $message = $this->get_result(
            $failures, 
            count($link_ids), 
            'PublicationNotDeleted', 
            'PublicationsNotDeleted', 
            'PublicationDeleted', 
            'PublicationsDeleted');
        
        return array($message, ($failures > 0));
    }

    public function delete_complex_wrapper($link_ids)
    {
        $failures = 0;
        
        foreach ($link_ids as $link_id)
        {
            $item = DataManager::retrieve_by_id(ComplexContentObjectItem::class_name(), $link_id);
            $object = DataManager::retrieve_by_id(ContentObject::class_name(), $item->get_ref());
            
            if (! $item->delete())
            {
                $failures ++;
                continue;
            }
            
            if (in_array($object->get_type(), DataManager::get_active_helper_types()))
            {
                if (! $object->delete())
                {
                    $failures ++;
                }
            }
        }
        
        $message = $this->get_result(
            $failures, 
            count($link_ids), 
            'ComplexContentObjectItemNotDeleted', 
            'ComplexContentObjectItemsNotDeleted', 
            'ComplexContentObjectItemDeleted', 
            'ComplexContentObjectItemsDeleted');
        
        return array($message, ($failures > 0));
    }

    public function delete_attachment($object_id, $link_ids)
    {
        $failures = 0;
        
        foreach ($link_ids as $link_id)
        {
            $object = DataManager::retrieve_by_id(ContentObject::class_name(), $object_id);
            if (! $object->detach_content_object($link_id, ContentObject::ATTACHMENT_NORMAL))
            {
                $failures ++;
            }
        }
        
        $message = $this->get_result(
            $failures, 
            count($link_ids), 
            'AttachmentNotDeleted', 
            'AttachmentsNotDeleted', 
            'AttachmentDeleted', 
            'AttachmentsDeleted');
        
        return array($message, ($failures > 0));
    }

    public function delete_attacher($object_id, $link_ids)
    {
        $failures = 0;
        
        foreach ($link_ids as $link_id)
        {
            $object = DataManager::retrieve_by_id(ContentObject::class_name(), $link_id);
            if (! $object->detach_content_object($object_id, ContentObject::ATTACHMENT_NORMAL))
            {
                $failures ++;
            }
        }
        
        $message = $this->get_result(
            $failures, 
            count($link_ids), 
            'AttacherNotDeleted', 
            'AttachersNotDeleted', 
            'AttacherDeleted', 
            'AttachersDeleted');
        
        return array($message, ($failures > 0));
    }

    public function delete_include($object_id, $link_ids)
    {
        $failures = 0;
        
        foreach ($link_ids as $link_id)
        {
        }
        
        $message = $this->get_result(
            $failures, 
            count($link_ids), 
            'PublicationNotDeleted', 
            'PublicationsNotDeleted', 
            'PublicationDeleted', 
            'PublicationsDeleted');
        
        return array($message, ($failures > 0));
    }

    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
        $breadcrumbtrail->add(
            new Breadcrumb(
                $this->get_url(array(self::PARAM_ACTION => self::ACTION_BROWSE_CONTENT_OBJECTS)), 
                Translation::get('BrowserComponent')));
        $breadcrumbtrail->add(
            new Breadcrumb(
                $this->get_url(
                    array(
                        self::PARAM_ACTION => self::ACTION_VIEW_CONTENT_OBJECTS, 
                        self::PARAM_CONTENT_OBJECT_ID => Request::get(self::PARAM_CONTENT_OBJECT_ID))), 
                Translation::get('RepositoryManagerViewerComponent')));
        $breadcrumbtrail->add_help('repository_link_deleter');
    }
}
