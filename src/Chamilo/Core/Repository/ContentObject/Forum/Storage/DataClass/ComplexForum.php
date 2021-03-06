<?php
namespace Chamilo\Core\Repository\ContentObject\Forum\Storage\DataClass;

use Chamilo\Core\Repository\ContentObject\Forum\EmailNotification\SubforumEmailNotificator;
use Chamilo\Core\Repository\ContentObject\ForumTopic\Storage\DataClass\ForumTopic;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Platform\Translation;

/**
 * $Id: complex_forum.class.php 200 2009-11-13 12:30:04Z kariboe $
 * 
 * @package repository.lib.content_object.forum
 */
class ComplexForum extends ComplexContentObjectItem
{

    public function get_allowed_types()
    {
        return array(Forum::class_name(), ForumTopic::class_name());
    }

    public function create()
    {
        $success = parent::create();
        
        $lo = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
            ContentObject::class_name(), 
            $this->get_ref());
        
        $parent = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
            ContentObject::class_name(), 
            $this->get_parent());
        
        $email_notificator = new SubforumEmailNotificator();
        $email_notificator->set_forum($parent);
        $email_notificator->set_subforum($lo);
        $text = Translation::get(
            "SubforumAddedEmailTitle", 
            null, 
            ContentObject::get_content_object_type_namespace('forum'));
        $email_notificator->set_action_title($text);
        $text = Translation::get(
            "SubforumAddedEmailBody", 
            null, 
            ContentObject::get_content_object_type_namespace('forum'));
        $email_notificator->set_action_body($text);
        $email_notificator->set_action_user(
            \Chamilo\Core\User\Storage\DataManager::retrieve_by_id(
                \Chamilo\Core\User\Storage\DataClass\User::class_name(), 
                (int) Session::get_user_id()));
        
        $parent->add_topic($lo->get_total_topics());
        $parent->add_post($lo->get_total_posts(), $email_notificator);
        $parent->recalculate_last_post();
        
        $email_notificator->send_emails();
        
        return $success;
    }

    public function delete()
    {
        $succes = parent::delete();
        
        $lo = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
            ContentObject::class_name(), 
            $this->get_ref());
        
        $parent = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
            ContentObject::class_name(), 
            $this->get_parent());
        
        $parent->remove_topic($lo->get_total_topics());
        $parent->remove_post($lo->get_total_posts());
        $parent->recalculate_last_post();
        
        return $succes;
    }
}
