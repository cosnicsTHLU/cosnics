<?php
namespace Chamilo\Core\Repository\Feedback;

/**
 * Interface which indicates a component implements the Feedback manager
 * 
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
interface FeedbackSupport
{

    /**
     * Retrieve a ResultSet of Feedback objects
     * 
     * @param integer $count
     * @param integer $offset
     * @return \Chamilo\Libraries\Storage\ResultSet\DataClassResultSet
     */
    public function retrieve_feedbacks($count, $offset);

    /**
     * Count the number of Feedback objects
     * 
     * @return int
     */
    public function count_feedbacks();

    /**
     * Retrieve a specific Feedback instance
     * 
     * @param int $feedback_id
     * @return \core\repository\content_object\portfolio\feedback\Feedback
     */
    public function retrieve_feedback($feedback_id);

    /**
     * Returns an newly instantiated Feedback object
     * 
     * @return \core\repository\content_object\portfolio\feedback\Feedback
     */
    public function get_feedback();

    /**
     * Is the user allowed to view feedback
     * 
     * @return boolean
     */
    public function is_allowed_to_view_feedback();

    /**
     * Is the user allowed to give feedback
     * 
     * @return boolean
     */
    public function is_allowed_to_create_feedback();

    /**
     * Is the user allowed to update this feedback instance
     * 
     * @param Feedback $feedback
     * @return boolean
     */
    public function is_allowed_to_update_feedback($feedback);

    /**
     * Is the user allowed to delete this feedback instance
     * 
     * @param Feedback $feedback
     * @return boolean
     */
    public function is_allowed_to_delete_feedback($feedback);
}
