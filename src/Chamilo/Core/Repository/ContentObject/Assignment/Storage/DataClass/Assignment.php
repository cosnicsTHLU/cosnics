<?php
namespace Chamilo\Core\Repository\ContentObject\Assignment\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\AttachmentSupport;

/**
 *
 * @package repository.content_object.assignment.php This class represents an assignment
 * @author Joris Willems <joris.willems@gmail.com>
 * @author Alexander Van Paemel
 */
class Assignment extends ContentObject implements AttachmentSupport
{
    const PROPERTY_START_TIME = 'start_time';
    const PROPERTY_END_TIME = 'end_time';
    const PROPERTY_VISIBILITY_SUBMISSIONS = 'visibility_submissions';
    const PROPERTY_ALLOW_GROUP_SUBMISSIONS = 'allow_group_submissions';
    const PROPERTY_ALLOW_LATE_SUBMISSIONS = 'allow_late_submissions';
    const PROPERTY_VISIBILTY_FEEDBACK = 'visibility_feedback';
    const PROPERTY_AUTOMATIC_FEEDBACK_TEXT = 'automatic_feedback_text';
    const PROPERTY_AUTOMATIC_FEEDBACK_CO_IDS = 'automatic_feedback_co_ids';
    const PROPERTY_SELECT_ATTACHMENT = 'select_attachment';
    const PROPERTY_ALLOWED_TYPES = 'allowed_types';
    const VISIBILITY_FEEDBACK_AFTER_END_TIME = 0;
    const VISIBILITY_FEEDBACK_AFTER_SUBMISSION = 1;

    public static function get_type_name()
    {
        return ClassnameUtilities::getInstance()->getClassNameFromNamespace(self::class_name(), true);
    }

    public static function get_additional_property_names()
    {
        return array(
            self::PROPERTY_START_TIME, 
            self::PROPERTY_END_TIME, 
            self::PROPERTY_VISIBILITY_SUBMISSIONS, 
            self::PROPERTY_ALLOW_GROUP_SUBMISSIONS, 
            self::PROPERTY_ALLOW_LATE_SUBMISSIONS, 
            self::PROPERTY_AUTOMATIC_FEEDBACK_TEXT, 
            self::PROPERTY_VISIBILTY_FEEDBACK, 
            self::PROPERTY_AUTOMATIC_FEEDBACK_CO_IDS, 
            self::PROPERTY_ALLOWED_TYPES);
    }

    public function get_allow_group_submissions()
    {
        return $this->get_additional_property(self::PROPERTY_ALLOW_GROUP_SUBMISSIONS);
    }

    public function set_allow_group_submissions($allow_group_submissions)
    {
        $this->set_additional_property(self::PROPERTY_ALLOW_GROUP_SUBMISSIONS, $allow_group_submissions);
    }

    public function get_start_time()
    {
        return $this->get_additional_property(self::PROPERTY_START_TIME);
    }

    public function set_start_time($start_time)
    {
        $this->set_additional_property(self::PROPERTY_START_TIME, $start_time);
    }

    public function get_end_time()
    {
        return $this->get_additional_property(self::PROPERTY_END_TIME);
    }

    public function set_end_time($end_time)
    {
        $this->set_additional_property(self::PROPERTY_END_TIME, $end_time);
    }

    public function get_visibility_submissions()
    {
        return $this->get_additional_property(self::PROPERTY_VISIBILITY_SUBMISSIONS);
    }

    public function set_visibility_submissions($visibility_submissions)
    {
        $this->set_additional_property(self::PROPERTY_VISIBILITY_SUBMISSIONS, $visibility_submissions);
    }

    public function get_allow_late_submissions()
    {
        return $this->get_additional_property(self::PROPERTY_ALLOW_LATE_SUBMISSIONS);
    }

    public function set_allow_late_submissions($allow_late_submissions)
    {
        $this->set_additional_property(self::PROPERTY_ALLOW_LATE_SUBMISSIONS, $allow_late_submissions);
    }

    public function get_visibility_feedback()
    {
        return $this->get_additional_property(self::PROPERTY_VISIBILTY_FEEDBACK);
    }

    public function set_visibility_feedback($visibility_feedback)
    {
        $this->set_additional_property(self::PROPERTY_VISIBILTY_FEEDBACK, $visibility_feedback);
    }

    public function get_automatic_feedback_text()
    {
        return $this->get_additional_property(self::PROPERTY_AUTOMATIC_FEEDBACK_TEXT);
    }

    public function set_automatic_feedback_text($automatic_feedback_text)
    {
        $this->set_additional_property(self::PROPERTY_AUTOMATIC_FEEDBACK_TEXT, $automatic_feedback_text);
    }

    public function get_automatic_feedback_co_ids()
    {
        return $this->get_additional_property(self::PROPERTY_AUTOMATIC_FEEDBACK_CO_IDS);
    }

    public function set_automatic_feedback_co_ids($automatic_feedback_co_ids)
    {
        $this->set_additional_property(self::PROPERTY_AUTOMATIC_FEEDBACK_CO_IDS, $automatic_feedback_co_ids);
    }

    public function get_allowed_types()
    {
        return $this->get_additional_property(self::PROPERTY_ALLOWED_TYPES);
    }

    public function set_allowed_types($allowed_types)
    {
        $this->set_additional_property(self::PROPERTY_ALLOWED_TYPES, $allowed_types);
    }
}
