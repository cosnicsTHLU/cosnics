<?php

namespace Chamilo\Application\Weblcms\Tool\Implementation\LearningPath\Component;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Template\WikiPageTemplate;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Template\WikiTemplate;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\ForumTopicView;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\LearningPathChildAttempt;
use Chamilo\Application\Weblcms\Rights\WeblcmsRights;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Tool\Implementation\LearningPath\Domain\LearningPathTrackingParameters;
use Chamilo\Application\Weblcms\Tool\Implementation\LearningPath\Manager;
use Chamilo\Application\Weblcms\Tool\Implementation\LearningPath\Storage\DataManager;
use Chamilo\Core\Repository\ContentObject\Assessment\Display\Interfaces\AssessmentDisplaySupport;
use Chamilo\Core\Repository\ContentObject\Assessment\Storage\DataClass\Assessment;
use Chamilo\Core\Repository\ContentObject\Blog\Display\BlogDisplaySupport;
use Chamilo\Core\Repository\ContentObject\Forum\Display\ForumDisplaySupport;
use Chamilo\Core\Repository\ContentObject\Glossary\Display\GlossaryDisplaySupport;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Embedder\Embedder;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\LearningPathDisplaySupport;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Renderer\LearningPathTreeRenderer;
use Chamilo\Core\Repository\ContentObject\LearningPath\Service\LearningPathTrackingService;
use Chamilo\Core\Repository\ContentObject\LearningPath\Service\LearningPathTrackingServiceBuilder;
use Chamilo\Core\Repository\ContentObject\LearningPath\Service\LearningPathTreeBuilder;
use Chamilo\Core\Repository\ContentObject\Wiki\Display\WikiDisplaySupport;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Tracking\Storage\DataClass\Event;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException;
use Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Structure\Page;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\DataManager\Repository\DataClassRepository;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\Utilities;

class ComplexDisplayComponent extends Manager implements LearningPathDisplaySupport, AssessmentDisplaySupport,
    ForumDisplaySupport, GlossaryDisplaySupport, BlogDisplaySupport, WikiDisplaySupport, DelegateComponent
{

    /**
     *
     * @var ContentObjectPublication
     */
    private $publication;

    /*
     * The question attempts @var QuestionAttempt[]
     */
    private $question_attempts;

    /**
     * @var LearningPathTrackingService
     */
    protected $learningPathTrackingService;

    public function run()
    {
        $contentObjectPublicationTranslation =
            Translation::getInstance()->getTranslation('ContentObjectPublication', null, 'Chamilo\Application\Weblcms');

        $publication_id = Request::get(\Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID);

        if (empty($publication_id))
        {
            throw new NoObjectSelectedException($contentObjectPublicationTranslation);
        }

        $this->set_parameter(\Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID, $publication_id);

        $this->publication = \Chamilo\Application\Weblcms\Storage\DataManager::retrieve_by_id(
            ContentObjectPublication::class_name(),
            $publication_id
        );

        if (!$this->publication instanceof ContentObjectPublication)
        {
            throw new ObjectNotExistException($contentObjectPublicationTranslation, $publication_id);
        }

        $this->buildLearningPathTrackingService();

        if (!$this->is_allowed(WeblcmsRights::VIEW_RIGHT, $this->publication))
        {
            $this->redirect(
                Translation::getInstance()->getTranslation("NotAllowed", null, Utilities::COMMON_LIBRARIES),
                true,
                array(),
                array(
                    \Chamilo\Application\Weblcms\Tool\Manager::PARAM_ACTION,
                    \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID
                )
            );
        }

        if ($this->get_root_content_object()->get_type() == Assessment::class_name())
        {
            try
            {
                $this->checkMaximumAssessmentAttempts();
            }
            catch (\Exception $ex)
            {
                $html = array();

                $html[] = $this->render_header();
                $html[] = '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
                $html[] = $this->render_footer();

                return implode(PHP_EOL, $html);
            }
        }

        // BreadcrumbTrail :: getInstance()->add(new Breadcrumb(null, $this->get_root_content_object()->get_title()));

        $context = $this->get_root_content_object()->package() . '\Display';
        $factory = new ApplicationFactory(
            $context,
            new ApplicationConfiguration($this->getRequest(), $this->getUser(), $this)
        );

        return $factory->run();
    }

    public function get_root_content_object()
    {
        if ($this->is_embedded())
        {
            $embedded_content_object_id = $this->get_embedded_content_object_id();
            $this->set_parameter(Embedder::PARAM_EMBEDDED_CONTENT_OBJECT_ID, $embedded_content_object_id);

            return \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                ContentObject::class_name(),
                $embedded_content_object_id
            );
        }
        else
        {
            $this->set_parameter(
                \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_LEARNING_PATH_ITEM_ID,
                Request::get(
                    \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_LEARNING_PATH_ITEM_ID
                )
            );
            $this->set_parameter(
                \Chamilo\Core\Repository\Display\Manager::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID,
                Request::get(\Chamilo\Core\Repository\Display\Manager::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID)
            );

            return $this->publication->get_content_object();
        }
    }

    /**
     *
     * @return boolean
     */
    function is_embedded()
    {
        $embedded_content_object_id = $this->get_embedded_content_object_id();

        return isset($embedded_content_object_id);
    }

    /**
     *
     * @return int
     */
    function get_embedded_content_object_id()
    {
        return Embedder::get_embedded_content_object_id();
    }

    /**
     * @param string $pageTitle
     *
     * @return string
     */
    public function render_header($pageTitle = '')
    {
        if ($this->is_embedded())
        {
            Page::getInstance()->setViewMode(Page::VIEW_MODE_HEADERLESS);

            return Application::render_header();
        }
        else
        {
            return parent::render_header();
        }
    }

    public function get_publication()
    {
        return $this->publication;
    }

    public function get_additional_parameters()
    {
        return array(
            \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID,
            \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_STEP,
            \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_FULL_SCREEN
        );
    }

    public function retrieve_learning_path_tracker()
    {
    }

    public function retrieve_learning_path_tracker_items($learning_path_tracker)
    {
    }

    public function get_learning_path_tree_menu_url()
    {
        $parameters = array();

        $parameters[Application::PARAM_CONTEXT] = \Chamilo\Application\Weblcms\Manager::context();
        $parameters[Application::PARAM_ACTION] = \Chamilo\Application\Weblcms\Manager::ACTION_VIEW_COURSE;
        $parameters[\Chamilo\Application\Weblcms\Manager::PARAM_COURSE] = Request::get('course');
        $parameters[\Chamilo\Application\Weblcms\Manager::PARAM_TOOL] =
            ClassnameUtilities::getInstance()->getPackageNameFromNamespace(
                $this->package()
            );
        $parameters[\Chamilo\Application\Weblcms\Tool\Manager::PARAM_ACTION] =
            \Chamilo\Application\Weblcms\Tool\Manager::ACTION_DISPLAY_COMPLEX_CONTENT_OBJECT;
        $parameters[\Chamilo\Application\Weblcms\Manager::PARAM_PUBLICATION] = $this->publication->getId();
        $parameters[\Chamilo\Core\Repository\Preview\Manager::PARAM_CONTENT_OBJECT_ID] =
            $this->get_root_content_object()->getId();

        $reportingActions = array(
            \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::ACTION_REPORTING,
            \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::ACTION_VIEW_ASSESSMENT_RESULT
        );

        if (
        in_array(
            $this->getRequest()->get(
                \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_ACTION
            ), $reportingActions
        ) || $this->getRequest()->get(\Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_REPORTING_MODE)
        )
        {
            $action = \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::ACTION_REPORTING;

            $parameters[\Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_REPORTING_USER_ID] =
                $this->getRequest()->get(
                    \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_REPORTING_USER_ID
                );
        }
        else
        {
            $action =
                \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::ACTION_VIEW_COMPLEX_CONTENT_OBJECT;
        }

        $parameters[\Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_ACTION] = $action;
        $parameters[\Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_CHILD_ID] =
            LearningPathTreeRenderer::NODE_PLACEHOLDER;
        $parameters[\Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_FULL_SCREEN] =
            $this->getRequest()->query->get(
                \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_FULL_SCREEN
            );

        $redirect = new Redirect($parameters);

        return $redirect->getUrl();
    }

    public function create_learning_path_item_tracker($learning_path_tracker, $current_complex_content_object_item)
    {
    }

    public function get_learning_path_content_object_assessment_result_url($complex_content_object_id, $details)
    {
        return $this->get_url(
            array(
                \Chamilo\Application\Weblcms\Tool\Manager::PARAM_ACTION => self::ACTION_DISPLAY_COMPLEX_CONTENT_OBJECT,
                \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID => $this->publication->getId(),
                \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_SHOW_PROGRESS => 'true',
                \Chamilo\Core\Repository\Display\Manager::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID => $complex_content_object_id,
                \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_DETAILS => $details
            )
        );
    }

    /**
     * Returns the currently selected learning path child id from the request
     *
     * @return int
     */
    public function getCurrentLearningPathChildId()
    {
        return (int) $this->getRequest()->get(
            \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_CHILD_ID, 0
        );
    }

    /**
     * Returns the LearningPathTree for the current learning path root
     *
     * @return \Chamilo\Core\Repository\ContentObject\LearningPath\Domain\LearningPathTree
     */
    protected function getLearningPathTree()
    {
        if (!isset($this->learningPathTree))
        {
            $this->learningPathTree = $this->getLearningPathTreeBuilder()->buildLearningPathTree(
                $this->publication->get_content_object()
            );
        }

        return $this->learningPathTree;
    }

    /**
     * Returns the LearningPathTreeNode for the current step
     *
     * @return \Chamilo\Core\Repository\ContentObject\LearningPath\Domain\LearningPathTreeNode
     */
    public function getCurrentLearningPathTreeNode()
    {
        $learningPathTree = $this->getLearningPathTree();

        return $learningPathTree->getLearningPathTreeNodeById($this->getCurrentLearningPathChildId());
    }

    public function save_assessment_answer($complex_question_id, $answer, $score, $hint)
    {
        $this->learningPathTrackingService->saveAnswerForQuestion(
            $this->publication->get_content_object(), $this->getUser(),
            $this->getCurrentLearningPathTreeNode(), $complex_question_id, $answer, $score, $hint
        );
    }

    public function save_assessment_result($total_score)
    {
        $this->learningPathTrackingService->saveAssessmentScore(
            $this->publication->get_content_object(), $this->getUser(), $this->getCurrentLearningPathTreeNode(),
            $total_score
        );
    }

    public function get_assessment_current_attempt_id()
    {
        return $this->get_parameter(
            \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_LEARNING_PATH_ITEM_ID
        );
    }

    public function get_assessment_configuration()
    {
        return $this->getCurrentLearningPathTreeNode()->getLearningPathChild()->getAssessmentConfiguration();
    }

    public function get_assessment_parameters()
    {
        return array(
            \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_LEARNING_PATH_ITEM_ID,
            \Chamilo\Core\Repository\Display\Manager::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID,
            \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager::PARAM_CHILD_ID
        );
    }

    /**
     * Returns the assessment question attempts
     *
     * @return \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Attempt\LearningPathQuestionAttempt[]
     */
    public function get_assessment_question_attempts()
    {
        if (is_null($this->question_attempts))
        {
            $this->question_attempts = $this->retrieve_question_attempts();
        }

        return $this->question_attempts;
    }

    /**
     * Retrieves the question attempts for the selected assessment attempt
     *
     * @return \Chamilo\Core\Repository\ContentObject\LearningPath\Display\Attempt\LearningPathQuestionAttempt[]
     */
    protected function retrieve_question_attempts()
    {
        return $this->learningPathTrackingService->getQuestionAttempts(
            $this->publication->get_content_object(), $this->getUser(), $this->getCurrentLearningPathTreeNode()
        );
    }

    /**
     * Registers the question ids
     *
     * @param int[] $question_ids
     */
    public function register_question_ids($question_ids)
    {
        $this->question_attempts = $this->learningPathTrackingService->registerQuestionAttempts(
            $this->publication->get_content_object(), $this->getUser(), $this->getCurrentLearningPathTreeNode(),
            $question_ids
        );
    }

    /**
     * Returns the registered question ids
     *
     * @return int[] $question_ids
     */
    public function get_registered_question_ids()
    {
        return array_keys($this->get_assessment_question_attempts());
    }

    public function get_assessment_question_attempt($complex_question_id)
    {
        return $this->question_attempts[$complex_question_id];
    }

    public function forum_topic_viewed($complex_topic_id)
    {
        $parameters = array();
        $parameters[ForumTopicView::PROPERTY_USER_ID] =
            $this->get_user_id();
        $parameters[ForumTopicView::PROPERTY_PUBLICATION_ID] =
            $this->get_publication()->getId();
        $parameters[ForumTopicView::PROPERTY_FORUM_TOPIC_ID] =
            $complex_topic_id;

        Event::trigger('ViewForumTopic', \Chamilo\Application\Weblcms\Manager::context(), $parameters);
    }

    public function forum_count_topic_views($complex_topic_id)
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ForumTopicView::class_name(),
                ForumTopicView::PROPERTY_PUBLICATION_ID
            ),
            new StaticConditionVariable($this->get_publication()->getId())
        );

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ForumTopicView::class_name(),
                ForumTopicView::PROPERTY_FORUM_TOPIC_ID
            ),
            new StaticConditionVariable($complex_topic_id)
        );
        $condition = new AndCondition($conditions);

        return DataManager::count(
            ForumTopicView::class_name(),
            new DataClassCountParameters($condition)
        );
    }

    /**
     * Returns whether or not the logged in user is a forum manager
     *
     * @param User $user
     *
     * @return bool
     */
    public function is_forum_manager($user)
    {
        return $this->get_course()->is_course_admin($user);
    }

    public function get_wiki_page_statistics_reporting_template_name()
    {
        return WikiPageTemplate::class_name();
    }

    public function get_wiki_statistics_reporting_template_name()
    {
        return WikiTemplate::class_name();
    }

    public function get_wiki_publication()
    {
        throw new \Exception("Unimplemented method : " . __CLASS__ . ':' . __METHOD__);
    }

    public function get_assessment_continue_url()
    {
    }

    public function get_assessment_back_url()
    {
    }

    public function get_assessment_current_url()
    {
        return $this->get_url(array(Embedder::PARAM_EMBEDDED_CONTENT_OBJECT_ID => null));
    }

    // METHODS FOR COMPLEX DISPLAY RIGHTS
    public function is_allowed_to_edit_content_object()
    {
        return $this->is_allowed(WeblcmsRights::EDIT_RIGHT, $this->publication) &&
            $this->publication->get_allow_collaboration();
    }

    public function is_allowed_to_view_content_object()
    {
        return $this->is_allowed(WeblcmsRights::VIEW_RIGHT, $this->publication);
    }

    public function is_allowed_to_add_child()
    {
        return $this->is_allowed(WeblcmsRights::EDIT_RIGHT, $this->publication);
    }

    public function is_allowed_to_delete_child()
    {
        return $this->is_allowed(WeblcmsRights::EDIT_RIGHT, $this->publication);
    }

    public function is_allowed_to_delete_feedback()
    {
        return $this->is_allowed(WeblcmsRights::EDIT_RIGHT, $this->publication);
    }

    public function is_allowed_to_edit_feedback()
    {
        return $this->is_allowed(WeblcmsRights::EDIT_RIGHT, $this->publication);
    }

    public function is_allowed_to_edit_learning_path_attempt_data()
    {
        return $this->is_allowed(WeblcmsRights::EDIT_RIGHT, $this->publication);
    }

    /**
     * @param int $learning_path_item_attempt_id
     *
     * @return \Chamilo\Libraries\Storage\DataClass\DataClass
     */
    public function retrieve_learning_path_item_attempt($learning_path_item_attempt_id)
    {
        return DataManager::retrieve_by_id(LearningPathChildAttempt::class_name(), $learning_path_item_attempt_id);
    }

    /**
     * Checks the maximum allowed assessment attempts
     */
    protected function checkMaximumAssessmentAttempts()
    {
        if ($this->learningPathTrackingService->isMaximumAttemptsReachedForAssessment(
            $this->publication->get_content_object(), $this->getUser(), $this->getCurrentLearningPathTreeNode()
        )
        )
        {
            throw new \Exception(
                Translation::getInstance()->getTranslation(
                    'YouHaveReachedYourMaximumAttempts',
                    null,
                    'Chamilo\Application\Weblcms\Tool\Implementation\Assessment'
                )
            );
        }
    }

    /**
     * Builds the LearningPathTrackingService
     *
     * @return LearningPathTrackingService
     */
    public function buildLearningPathTrackingService()
    {
        if (!isset($this->learningPathTrackingService))
        {
            $this->learningPathTrackingService = $this->createLearningPathTrackingServiceForPublicationAndCourse(
                (int) $this->publication->getId(), (int) $this->get_course_id()
            );
        }

        return $this->learningPathTrackingService;
    }
}
