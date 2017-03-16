<?php
namespace Chamilo\Core\Repository\ContentObject\LearningPath\Display\Component;

use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager;
use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\LearningPathTreeNode;
use Chamilo\Core\Repository\Integration\Chamilo\Core\Tracking\Storage\DataClass\Activity;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Tracking\Storage\DataClass\Event;
use Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException;
use Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException;
use Chamilo\Libraries\Architecture\Exceptions\UserException;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 *
 * @package core\repository\content_object\learning_path\display
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class DeleterComponent extends Manager
{

    /**
     * Executes this component
     */
    public function run()
    {
        $this->validateCurrentNode();

        $selected_steps = $this->getRequest()->get(self::PARAM_STEP);
        if (!is_array($selected_steps))
        {
            $selected_steps = array($selected_steps);
        }

        $learningPathTree = $this->getLearningPathTree();

        /** @var LearningPathTreeNode[] $available_nodes */
        $available_nodes = array();

        foreach ($selected_steps as $selected_step)
        {
            try
            {
                $selected_node = $learningPathTree->getLearningPathTreeNodeByStep((int) $selected_step);

                if ($this->canEditLearningPathTreeNode($selected_node->getParentNode()))
                {
                    $available_nodes[] = $selected_node;
                }
            }
            catch (\Exception $ex)
            {
                throw new ObjectNotExistException(Translation::getInstance()->getTranslation('Step'), $selected_step);
            }
        }

        if (count($available_nodes) == 0)
        {
            throw new UserException(
                Translation::get(
                    'NoObjectsToDelete',
                    array('OBJECTS' => Translation::get('ComplexContentObjectItems')),
                    Utilities::COMMON_LIBRARIES
                )
            );
        }

        $failures = 0;

        $learningPathChildService = $this->getLearningPathChildService();

        $new_node = null;

        foreach ($available_nodes as $available_node)
        {
            try
            {
                $learningPathChildService->deleteContentObjectFromLearningPath($available_node);
                $success = true;
            }
            catch (\Exception $ex)
            {
                $success = false;
            }

            $current_parents_content_object_ids = $available_node->getPathAsContentObjectIds();

            if ($success)
            {
                Event::trigger(
                    'Activity',
                    \Chamilo\Core\Repository\Manager::context(),
                    array(
                        Activity::PROPERTY_TYPE => Activity::ACTIVITY_DELETE_ITEM,
                        Activity::PROPERTY_USER_ID => $this->get_user_id(),
                        Activity::PROPERTY_DATE => time(),
                        Activity::PROPERTY_CONTENT_OBJECT_ID => $available_node->getParentNode()->getContentObject()
                            ->getId(),
                        Activity::PROPERTY_CONTENT => $available_node->getParentNode()->getContentObject()
                                ->get_title() . ' > ' . $available_node->getContentObject()->get_title()
                    )
                );
            }
            else
            {
                $failures ++;
            }

            $this->recalculateLearningPathTree();

            $new_node = $this->getLearningPathTree()
                ->getLearningPathTreeNodeForContentObjectIdentifiedByParentContentObjects(
                    $current_parents_content_object_ids
                );
        }

        $parameters = array();
        $parameters[self::PARAM_ACTION] = self::ACTION_VIEW_COMPLEX_CONTENT_OBJECT;

        if ($failures > 0)
        {
            $parameters[self::PARAM_STEP] = $this->getCurrentLearningPathTreeNode()->getParentNode()->getStep();
        }
        else
        {
            $parameters[self::PARAM_STEP] = $new_node->getStep();
        }

        $this->redirect(
            Translation::get(
                $failures > 0 ? 'ObjectsNotDeleted' : 'ObjectsDeleted',
                array('OBJECTS' => Translation::get('ComplexContentObjectItems')),
                Utilities::COMMON_LIBRARIES
            ),
            $failures > 0,
            array(
                self::PARAM_ACTION => self::ACTION_VIEW_COMPLEX_CONTENT_OBJECT,
                self::PARAM_STEP => $new_node->getStep()
            ),
            array(self::PARAM_CONTENT_OBJECT_ID)
        );
    }

    public function get_additional_parameters()
    {
        return array(self::PARAM_STEP, self::PARAM_FULL_SCREEN, self::PARAM_CONTENT_OBJECT_ID);
    }
}
