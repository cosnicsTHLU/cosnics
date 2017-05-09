<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Display\Ajax\Component;

use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Ajax\Manager;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Architecture\JsonAjaxResult;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Changes the title of a given LearningPathTreeNode
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class UpdateLearningPathTreeNodeTitleComponent extends Manager
{
    const PARAM_NEW_TITLE = 'new_title';
    const PARAM_CHILD_ID = 'child_id';

    /**
     * Executes this component and returns its output
     */
    public function run()
    {
        try
        {
            $childId = $this->getRequestedPostDataValue(self::PARAM_CHILD_ID);

            $learningPathTree = $this->get_application()->getLearningPathTree();
            $learningPathTreeNode = $learningPathTree->getLearningPathTreeNodeById((int) $childId);

            $learningPathChildService = $this->get_application()->getLearningPathChildService();

            if (!$this->get_application()->canEditLearningPathTreeNode($learningPathTreeNode))
            {
                throw new NotAllowedException();
            }

            $learningPathChildService->updateContentObjectTitle(
                $learningPathTreeNode, $this->getRequestedPostDataValue(self::PARAM_NEW_TITLE)
            );

            return new JsonResponse(null, 200);
        }
        catch (\Exception $ex)
        {
            return new JsonResponse(null, 500);
        }
    }

    /**
     * Returns the required post parameters
     *
     * @return string
     */
    public function getRequiredPostParameters()
    {
        return array(self::PARAM_NEW_TITLE, self::PARAM_CHILD_ID);
    }
}