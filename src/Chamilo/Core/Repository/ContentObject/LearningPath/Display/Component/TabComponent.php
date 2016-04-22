<?php
namespace Chamilo\Core\Repository\ContentObject\LearningPath\Display\Component;

use Chamilo\Core\Repository\Common\Path\ComplexContentObjectPathNode;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Menu;
use Chamilo\Core\Repository\ContentObject\LearningPath\Storage\DataClass\LearningPath;
use Chamilo\Core\Repository\Selector\TypeSelector;
use Chamilo\Core\Repository\Workspace\Service\RightsService;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Tabs\DynamicVisualTab;
use Chamilo\Libraries\Format\Tabs\DynamicVisualTabsRenderer;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;

abstract class TabComponent extends Manager implements DelegateComponent
{

    private $learning_path_menu;

    public function get_prerequisites_url($selected_complex_content_object_item)
    {
        $complex_content_object_item_id = ($this->get_complex_content_object_item()) ? ($this->get_complex_content_object_item()->get_id()) : null;

        return $this->get_url(
            array(
                self :: PARAM_ACTION => self :: ACTION_BUILD_PREREQUISITES,
                self :: PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID => $complex_content_object_item_id,
                self :: PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $selected_complex_content_object_item));
    }

    public function get_mastery_score_url($selected_complex_content_object_item)
    {
        $complex_content_object_item_id = ($this->get_complex_content_object_item()) ? ($this->get_complex_content_object_item()->get_id()) : null;

        return $this->get_url(
            array(
                self :: PARAM_ACTION => self :: ACTION_SET_MASTERY_SCORE,
                self :: PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID => $complex_content_object_item_id,
                self :: PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $selected_complex_content_object_item));
    }

    public function get_configuration_url($selected_complex_content_object_item)
    {
        $complex_content_object_item_id = ($this->get_complex_content_object_item()) ? ($this->get_complex_content_object_item()->get_id()) : null;

        return $this->get_url(
            array(
                self :: PARAM_ACTION => self :: ACTION_CONFIGURE_FEEDBACK,
                self :: PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID => $complex_content_object_item_id,
                self :: PARAM_SELECTED_COMPLEX_CONTENT_OBJECT_ITEM_ID => $selected_complex_content_object_item));
    }

    public function run()
    {
        $learning_path = $this->get_parent()->get_root_content_object();

        $trail = BreadcrumbTrail :: get_instance();

        if (! $learning_path)
        {
            return $this->display_error_page(Translation :: get('NoObjectSelected'));
        }

        $this->learning_path_menu = new Menu($this);

        $this->set_complex_content_object_item($this->get_current_complex_content_object_item());

        foreach ($this->get_root_content_object()->get_complex_content_object_path()->get_parents_by_id(
            $this->get_current_step(),
            true,
            true) as $node_parent)
        {
            $parameters = $this->get_parameters();
            $parameters[self :: PARAM_STEP] = $node_parent->get_id();
            $parameters[self::PARAM_ACTION] = self::ACTION_VIEW_COMPLEX_CONTENT_OBJECT;
            BreadcrumbTrail :: get_instance()->add(
                new Breadcrumb($this->get_url($parameters), $node_parent->get_content_object()->get_title()));
        }

        $this->tabs_renderer = new DynamicVisualTabsRenderer('learning_path');

        if ($this->get_action() == self :: ACTION_REPORTING && ! $this->is_current_step_set())
        {
            $this->tabs_renderer->add_tab(
                new DynamicVisualTab(
                    self :: ACTION_REPORTING,
                    Translation :: get('ReportingComponent'),
                    Theme :: getInstance()->getImagePath(Manager :: package(), 'Tab/' . self :: ACTION_REPORTING),
                    $this->get_url(array(self :: PARAM_ACTION => self :: ACTION_REPORTING)),
                    $this->get_action() == self :: ACTION_REPORTING,
                    false,
                    DynamicVisualTab :: POSITION_LEFT,
                    DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
        }
        else
        {
            $this->tabs_renderer->add_tab(
                new DynamicVisualTab(
                    self :: ACTION_VIEW_COMPLEX_CONTENT_OBJECT,
                    Translation :: get('ViewerComponent'),
                    Theme :: getInstance()->getImagePath(
                        Manager :: package(),
                        'Tab/' . self :: ACTION_VIEW_COMPLEX_CONTENT_OBJECT),
                    $this->get_url(
                        array(
                            self :: PARAM_ACTION => self :: ACTION_VIEW_COMPLEX_CONTENT_OBJECT,
                            self :: PARAM_STEP => $this->get_current_step())),
                    $this->get_action() == self :: ACTION_VIEW_COMPLEX_CONTENT_OBJECT,
                    false,
                    DynamicVisualTab :: POSITION_LEFT,
                    DynamicVisualTab :: DISPLAY_BOTH_SELECTED));

            $edit_title = Translation :: get('UpdaterComponent');
            $edit_image = Theme :: getInstance()->getImagePath(
                Manager :: package(),
                'Tab/' . self :: ACTION_UPDATE_COMPLEX_CONTENT_OBJECT_ITEM);

            $current_content_object = $this->get_current_node()->get_content_object();

            if ($this->get_parent()->is_allowed_to_edit_content_object($this->get_current_node()) &&
                 RightsService :: getInstance()->canEditContentObject($this->get_user(), $current_content_object))
            {
                $this->tabs_renderer->add_tab(
                    new DynamicVisualTab(
                        self :: ACTION_UPDATE_COMPLEX_CONTENT_OBJECT_ITEM,
                        $edit_title,
                        $edit_image,
                        $this->get_url(
                            array(
                                self :: PARAM_ACTION => self :: ACTION_UPDATE_COMPLEX_CONTENT_OBJECT_ITEM,
                                self :: PARAM_STEP => $this->get_current_step())),
                        $this->get_action() == self :: ACTION_UPDATE_COMPLEX_CONTENT_OBJECT_ITEM,
                        false,
                        DynamicVisualTab :: POSITION_LEFT,
                        DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
            }

            if (! $this->get_current_node()->get_content_object() instanceof LearningPath &&
                 $this->get_parent()->is_allowed_to_edit_content_object($this->get_current_node()))
            {
                $this->tabs_renderer->add_tab(
                    new DynamicVisualTab(
                        self :: ACTION_BUILD_PREREQUISITES,
                        Translation :: get('BuildPrerequisites'),
                        Theme :: getInstance()->getImagePath(
                            Manager :: package(),
                            'Tab/' . self :: ACTION_BUILD_PREREQUISITES),
                        $this->get_url(
                            array(
                                self :: PARAM_ACTION => self :: ACTION_BUILD_PREREQUISITES,
                                self :: PARAM_STEP => $this->get_current_step())),
                        $this->get_action() == self :: ACTION_BUILD_PREREQUISITES,
                        false,
                        DynamicVisualTab :: POSITION_LEFT,
                        DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
            }

            foreach ($this->get_node_specific_tabs($this->get_current_node()) as $tab)
            {
                $this->tabs_renderer->add_tab($tab);
            }

            $this->tabs_renderer->add_tab(
                new DynamicVisualTab(
                    self :: ACTION_ACTIVITY,
                    Translation :: get('ActivityComponent'),
                    Theme :: getInstance()->getImagePath(Manager :: package(), 'Tab/' . self :: ACTION_ACTIVITY),
                    $this->get_url(
                        array(
                            self :: PARAM_ACTION => self :: ACTION_ACTIVITY,
                            self :: PARAM_STEP => $this->get_current_step())),
                    $this->get_action() == self :: ACTION_ACTIVITY,
                    false,
                    DynamicVisualTab :: POSITION_LEFT,
                    DynamicVisualTab :: DISPLAY_BOTH_SELECTED));

            $this->tabs_renderer->add_tab(
                new DynamicVisualTab(
                    self :: ACTION_REPORTING,
                    Translation :: get('ReportingComponent'),
                    Theme :: getInstance()->getImagePath(Manager :: package(), 'Tab/' . self :: ACTION_REPORTING),
                    $this->get_url(
                        array(
                            self :: PARAM_ACTION => self :: ACTION_REPORTING,
                            self :: PARAM_STEP => $this->get_current_step())),
                    $this->get_action() == self :: ACTION_REPORTING,
                    false,
                    DynamicVisualTab :: POSITION_LEFT,
                    DynamicVisualTab :: DISPLAY_BOTH_SELECTED));

            if (! $this->get_current_node()->is_root() &&
                 $this->get_parent()->is_allowed_to_edit_content_object($this->get_current_node()->get_parent()))
            {
                $variable = $this->get_current_content_object() instanceof LearningPath ? 'DeleteFolder' : 'DeleterComponent';

                $this->tabs_renderer->add_tab(
                    new DynamicVisualTab(
                        self :: ACTION_DELETE_COMPLEX_CONTENT_OBJECT_ITEM,
                        Translation :: get($variable),
                        Theme :: getInstance()->getImagePath(
                            Manager :: package(),
                            'Tab/' . self :: ACTION_DELETE_COMPLEX_CONTENT_OBJECT_ITEM),
                        $this->get_url(
                            array(
                                self :: PARAM_ACTION => self :: ACTION_DELETE_COMPLEX_CONTENT_OBJECT_ITEM,
                                self :: PARAM_STEP => $this->get_current_step())),
                        $this->get_action() == self :: ACTION_DELETE_COMPLEX_CONTENT_OBJECT_ITEM,
                        true,
                        DynamicVisualTab :: POSITION_RIGHT,
                        DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
            }

            if ($this->get_parent()->is_allowed_to_edit_content_object($this->get_current_node()))
            {
                if ($this->get_current_content_object() instanceof LearningPath &&
                     count($this->get_current_node()->get_children()) > 1)
                {
                    $this->tabs_renderer->add_tab(
                        new DynamicVisualTab(
                            self :: ACTION_MANAGE,
                            Translation :: get('ManagerComponent'),
                            Theme :: getInstance()->getImagePath(Manager :: package(), 'Tab/' . self :: ACTION_MANAGE),
                            $this->get_url(
                                array(
                                    self :: PARAM_ACTION => self :: ACTION_MANAGE,
                                    self :: PARAM_STEP => $this->get_current_step())),
                            $this->get_action() == self :: ACTION_MANAGE,
                            false,
                            DynamicVisualTab :: POSITION_RIGHT,
                            DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
                }
            }

            if (! $this->get_current_node()->is_root() &&
                 $this->get_parent()->is_allowed_to_edit_content_object($this->get_current_node()->get_parent()))
            {
                $variable = $this->get_current_content_object() instanceof LearningPath ? 'MoveFolder' : 'MoverComponent';

                $this->tabs_renderer->add_tab(
                    new DynamicVisualTab(
                        self :: ACTION_MOVE,
                        Translation :: get($variable),
                        Theme :: getInstance()->getImagePath(Manager :: package(), 'Tab/' . self :: ACTION_MOVE),
                        $this->get_url(
                            array(
                                self :: PARAM_ACTION => self :: ACTION_MOVE,
                                self :: PARAM_STEP => $this->get_current_step())),
                        $this->get_action() == self :: ACTION_MOVE,
                        false,
                        DynamicVisualTab :: POSITION_RIGHT,
                        DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
            }

            if ($this->get_parent()->is_allowed_to_edit_content_object($this->get_current_node()))
            {
                $template = \Chamilo\Core\Repository\Configuration :: registration_default_by_type(
                    LearningPath :: package());

                $selected_template_id = TypeSelector :: get_selection();

                $is_selected = ($this->get_action() == self :: ACTION_CREATE_COMPLEX_CONTENT_OBJECT_ITEM &&
                     $selected_template_id != $template->get_id());

                if ($this->get_current_node()->get_content_object() instanceof LearningPath)
                {
                    $step = $this->get_current_step();
                }
                else
                {
                    $step = $this->get_current_node()->get_parent()->get_id();
                }

                $this->tabs_renderer->add_tab(
                    new DynamicVisualTab(
                        self :: ACTION_CREATE_COMPLEX_CONTENT_OBJECT_ITEM,
                        Translation :: get('CreatorComponent'),
                        Theme :: getInstance()->getImagePath(
                            Manager :: package(),
                            'Tab/' . self :: ACTION_CREATE_COMPLEX_CONTENT_OBJECT_ITEM),
                        $this->get_url(
                            array(
                                self :: PARAM_ACTION => self :: ACTION_CREATE_COMPLEX_CONTENT_OBJECT_ITEM,
                                self :: PARAM_STEP => $step)),
                        $is_selected,
                        false,
                        DynamicVisualTab :: POSITION_RIGHT,
                        DynamicVisualTab :: DISPLAY_BOTH_SELECTED));

                $is_selected = ($this->get_action() == self :: ACTION_CREATE_COMPLEX_CONTENT_OBJECT_ITEM &&
                     $selected_template_id == $template->get_id());

                $this->tabs_renderer->add_tab(
                    new DynamicVisualTab(
                        self :: ACTION_CREATE_COMPLEX_CONTENT_OBJECT_ITEM,
                        Translation :: get('AddFolder'),
                        Theme :: getInstance()->getImagePath(Manager :: package(), 'Tab/Folder'),
                        $this->get_url(
                            array(
                                self :: PARAM_ACTION => self :: ACTION_CREATE_COMPLEX_CONTENT_OBJECT_ITEM,
                                self :: PARAM_STEP => $step,
                                TypeSelector :: PARAM_SELECTION => $template->get_id())),
                        $is_selected,
                        false,
                        DynamicVisualTab :: POSITION_RIGHT,
                        DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
            }

            if (! $this->get_current_node()->is_root() &&
                 $this->get_parent()->is_allowed_to_edit_content_object($this->get_current_node()->get_parent()) &&
                 $this->get_current_node()->has_siblings())
            {
                if (! $this->get_current_node()->is_last_child())
                {
                    $this->tabs_renderer->add_tab(
                        new DynamicVisualTab(
                            self :: ACTION_SORT,
                            Translation :: get('MoveDown'),
                            Theme :: getInstance()->getImagePath(
                                Manager :: package(),
                                'Tab/' . self :: ACTION_SORT . self :: SORT_DOWN),
                            $this->get_url(
                                array(
                                    self :: PARAM_ACTION => self :: ACTION_SORT,
                                    self :: PARAM_SORT => self :: SORT_DOWN,
                                    self :: PARAM_STEP => $this->get_current_step())),
                            $this->get_action() == self :: ACTION_SORT,
                            false,
                            DynamicVisualTab :: POSITION_RIGHT,
                            DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
                }
                else
                {
                    $this->tabs_renderer->add_tab(
                        new DynamicVisualTab(
                            self :: ACTION_SORT,
                            Translation :: get('MoveDownNotAvailable'),
                            Theme :: getInstance()->getImagePath(
                                Manager :: package(),
                                'Tab/' . self :: ACTION_SORT . self :: SORT_DOWN . 'Na'),
                            null,
                            false,
                            false,
                            DynamicVisualTab :: POSITION_RIGHT,
                            DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
                }

                if (! $this->get_current_node()->is_first_child())
                {
                    $this->tabs_renderer->add_tab(
                        new DynamicVisualTab(
                            self :: ACTION_SORT,
                            Translation :: get('MoveUp'),
                            Theme :: getInstance()->getImagePath(
                                Manager :: package(),
                                'Tab/' . self :: ACTION_SORT . self :: SORT_UP),
                            $this->get_url(
                                array(
                                    self :: PARAM_ACTION => self :: ACTION_SORT,
                                    self :: PARAM_SORT => self :: SORT_UP,
                                    self :: PARAM_STEP => $this->get_current_step())),
                            $this->get_action() == self :: ACTION_SORT,
                            false,
                            DynamicVisualTab :: POSITION_RIGHT,
                            DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
                }
                else
                {
                    $this->tabs_renderer->add_tab(
                        new DynamicVisualTab(
                            self :: ACTION_SORT,
                            Translation :: get('MoveUpNotAvailable'),
                            Theme :: getInstance()->getImagePath(
                                Manager :: package(),
                                'Tab/' . self :: ACTION_SORT . self :: SORT_UP . 'Na'),
                            null,
                            false,
                            false,
                            DynamicVisualTab :: POSITION_RIGHT,
                            DynamicVisualTab :: DISPLAY_BOTH_SELECTED));
                }
            }
        }

        return $this->build();
    }

    abstract function build();

    /**
     *
     * @see \libraries\SubManager::render_header()
     */
    public function render_header()
    {
        $html = array();

        $html[] = parent :: render_header();

        // Menu
        $html[] = '<div class="col-md-3 col-lg-2 col-sm-12 learning-path-content">';

        $html[] = '<div class="learning-path-tree-menu">';
        $html[] = $this->learning_path_menu->render_as_tree();
        $html[] = '</div>';
        $html[] = '<div class="clearfix"></div>';

        $html[] = '<div class="learning-path-progress">';
        $html[] = $this->get_progress_bar();
        $html[] = '</div>';
        $html[] = '<div class="clearfix"></div>';

        $html[] = $this->get_navigation_bar();

        $html[] = '</div>';

        // Content
        $html[] = '<div class="col-md-9 col-lg-10 col-sm-12 learning-path-content">';

        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @see \libraries\SubManager::render_footer()
     */
    public function render_footer()
    {
        $html = array();

        $html[] = '</div>';
        $html[] = '<div class="clearfix"></div>';
        $html[] = parent :: render_footer();

        return implode(PHP_EOL, $html);
    }

    /**
     * Get the TabsRenderer
     *
     * @return \libraries\format\DynamicVisualTabsRenderer
     */
    public function get_tabs_renderer()
    {
        return $this->tabs_renderer;
    }

    public function get_learning_path_menu()
    {
        return $this->learning_path_menu;
    }

    public function get_node_specific_tabs(ComplexContentObjectPathNode $node)
    {
        $object_namespace = $node->get_content_object()->package();
        $integration_class_name = $object_namespace . '\Integration\\' . self :: package() . '\Manager';

        if (class_exists($integration_class_name))
        {
            try
            {
                $factory = new ApplicationFactory(
                    $integration_class_name :: context(),
                    new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this));
                $component = $factory->getComponent();

                return $component->get_node_tabs($node);
            }
            catch (\Exception $exception)
            {
                return array();
            }
        }
        else
        {
            return array();
        }
    }

    /**
     * Retrieves the navigation menu for the learning path
     *
     * @param $total_steps int
     * @param $current_step int
     * @param $current_content_object ContentObject
     */
    private function get_navigation_bar()
    {
        $current_node = $this->get_current_node();
        $html = array();

        if ($this->get_action() != self :: ACTION_REPORTING || $this->is_current_step_set())
        {
            $html[] = '<div class="learning-path-navigation">';

            $previous_node = $current_node->get_previous();

            if ($previous_node instanceof ComplexContentObjectPathNode)
            {

                $previous_url = $this->get_url(
                    array(
                        self :: PARAM_ACTION => self :: ACTION_VIEW_COMPLEX_CONTENT_OBJECT,
                        self :: PARAM_STEP => $previous_node->get_id()));

                $label = Translation :: get('Previous');

                $html[] = '<a class="pull-left" href="' . $previous_url .
                     '"><span class="glyphicon glyphicon-arrow-left" alt="' . $label . '" title="' . $label .
                     '"></span></a>';
            }
            else
            {
                $label = Translation :: get('PreviousNa');

                $html[] = '<span class="glyphicon glyphicon-arrow-left disabled" alt="' . $label . '" title="' . $label .
                     '"></span>';
            }

            $next_node = $current_node->get_next();

            if ($next_node instanceof ComplexContentObjectPathNode)
            {
                $next_url = $this->get_url(
                    array(
                        self :: PARAM_ACTION => self :: ACTION_VIEW_COMPLEX_CONTENT_OBJECT,
                        self :: PARAM_STEP => $next_node->get_id()));

                $label = Translation :: get('Next');

                $html[] = '<a class="pull-right" href="' . $next_url .
                     '"><span class="glyphicon glyphicon-arrow-right" alt="' . $label . '" title="' . $label .
                     '"></span></a>';
            }
            else
            {
                $label = Translation :: get('NextNa');

                $html[] = '<span class="glyphicon glyphicon-arrow-right disabled" alt="' . $label . '" title="' . $label .
                     '"></span>';
            }

            $html[] = '</div>';
            $html[] = '<div class="clearfix"></div>';
        }

        return implode(PHP_EOL, $html);
    }

    /**
     * Renders the progress bar for the learning path
     *
     * @return array() HTML code of the progress bar
     */
    private function get_progress_bar()
    {
        $progress = $this->get_complex_content_object_path()->get_progress();

        return $this->render_progress_bar($progress);
    }

    /**
     *
     * @param integer $percent
     * @param integer $step
     *
     * @return string
     */
    private function render_progress_bar($percent, $step = 2)
    {
        $displayPercent = round($percent);

        $html[] = '<div class="progress">';
        $html[] = '<div class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="' . $displayPercent .
             '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $displayPercent . '%; min-width: 2em;">';
        $html[] = $displayPercent . '%';
        $html[] = '</div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }
}