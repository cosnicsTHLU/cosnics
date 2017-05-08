<?php

namespace Chamilo\Application\Weblcms\Tool\Implementation\LearningPath\Table\Publication;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\LearningPathAttempt;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Table\Publication\Table\ObjectPublicationTableCellRenderer;
use Chamilo\Application\Weblcms\Tool\Implementation\LearningPath\Domain\LearningPathTrackingParameters;
use Chamilo\Application\Weblcms\Tool\Implementation\LearningPath\Manager;
use Chamilo\Application\Weblcms\Tool\Implementation\LearningPath\Storage\DataManager;
use Chamilo\Core\Repository\ContentObject\LearningPath\Service\LearningPathTrackingService;
use Chamilo\Core\Repository\ContentObject\LearningPath\Service\LearningPathTrackingServiceBuilder;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\LearningPath;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Format\Structure\ProgressBarRenderer;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\DataManager\Repository\DataClassRepository;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\String\Text;

/**
 * Extension on the content object publication table cell renderer for this tool
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class PublicationTableCellRenderer extends ObjectPublicationTableCellRenderer
{

    /**
     * **************************************************************************************************************
     * Inherited Functionality *
     * **************************************************************************************************************
     */

    /**
     * Renders a cell for a given object
     *
     * @param $column \libraries\ObjectTableColumn
     *
     * @param mixed $publication
     *
     * @return String
     */
    public function render_cell($column, $publication)
    {
        switch ($column->get_name())
        {
            case PublicationTableColumnModel::COLUMN_PROGRESS :
            {
                if (!$this->get_component()->get_tool_browser()->get_parent()->is_empty_learning_path($publication))
                {
                    return $this->get_progress($publication);
                }
                else
                {
                    return Translation::get('EmptyLearningPath');
                }
            }
        }

        return parent::render_cell($column, $publication);
    }

    /**
     * **************************************************************************************************************
     * Helper Functionality *
     * **************************************************************************************************************
     */

    /**
     * Returns the progress of a given publication
     *
     * @param mixed[] $publication
     *
     * @return string
     */
    public function get_progress($publication)
    {
        /** @var LearningPathTrackingService $learningPathTrackingService */
        $learningPathTrackingService = $this->get_component()->get_tool_browser()->get_parent()
            ->createLearningPathTrackingServiceForPublicationAndCourse(
                $publication[ContentObjectPublication::PROPERTY_ID],
                $publication[ContentObjectPublication::PROPERTY_COURSE_ID]
            );

        /** @var User $user */
        $user = $this->get_component()->get_tool_browser()->get_parent()->getUser();

        $learningPath = new LearningPath();
        $learningPath->setId($publication[ContentObjectPublication::PROPERTY_CONTENT_OBJECT_ID]);

        $progress = $learningPathTrackingService->getLearningPathProgress(
            $learningPath, $user,
                $this->get_component()->get_tool_browser()->get_parent()->getCurrentLearningPathTreeNode($learningPath)
        );

        if (!is_null($progress))
        {
            $progressBarRenderer = new ProgressBarRenderer();
            $bar = $progressBarRenderer->render($progress);
        }

        $url = $this->get_component()->get_url(
            array(
                Manager::PARAM_ACTION => Manager::ACTION_DISPLAY_COMPLEX_CONTENT_OBJECT,
                \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID => $publication[ContentObjectPublication::PROPERTY_ID],
                \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_ACTION =>
                    \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::ACTION_REPORTING
            )
        );

        return Text::create_link($url, $bar);
    }
}