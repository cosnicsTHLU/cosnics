<?php
namespace Chamilo\Application\Weblcms\Component;

use Chamilo\Application\Weblcms\Manager;
use Chamilo\Application\Weblcms\Renderer\CourseList\Type\CourseTypeCourseListRenderer;
use Chamilo\Application\Weblcms\Rights\CourseManagementRights;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\DropdownButton;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\ActionBar\SubButton;
use Chamilo\Libraries\Format\Structure\Glyph\BootstrapGlyph;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Configuration\Configuration;

/**
 *
 * @package Chamilo\Application\Weblcms\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class CourseListComponent extends Manager implements DelegateComponent
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->checkAuthorization(Manager::context(), 'ViewPersonalCourses');
        
        $html = array();
        
        $html[] = $this->render_header();
        
        $html[] = '<div class="row">';
        
        $html[] = '<div class="col-md-9">';
        $html[] = $this->getCourseListRenderer()->as_html();
        $html[] = '</div>';
        
        $html[] = '<div class="col-md-3">';
        $html[] = $this->renderMenu();
        $html[] = '</div>';
        
        $html[] = '</div>';
        
        $html[] = $this->render_footer();
        
        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @return \Chamilo\Application\Weblcms\Renderer\CourseList\Type\CourseTypeCourseListRenderer
     */
    protected function getCourseListRenderer()
    {
        $renderer = new CourseTypeCourseListRenderer($this);
        $renderer->show_new_publication_icons();
        
        return $renderer;
    }

    /**
     *
     * @return string
     */
    protected function renderMenu()
    {
        $buttonToolBar = new ButtonToolBar();
        $buttonToolBar->addClass('btn-action-toolbar-vertical');
        
        if ($this->get_user()->is_platform_admin())
        {
            $buttonToolBar->addButtonGroup($this->buildAdminCourseManagementButtonGroup($buttonToolBar));
        }
        elseif ($this->get_user()->is_teacher() && ($_SESSION["studentview"] != "studentenview"))
        {
            $buttonToolBar->addButtonGroup($this->buildTeacherManagementButtonGroup());
        }
        
        $buttonToolBar->addButtonGroup($this->buildUserManagementButtonGroup());
        
        $buttonToolBarRenderer = new ButtonToolBarRenderer($buttonToolBar);
        
        return $buttonToolBarRenderer->render();
    }

    /**
     *
     * @return \Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup
     */
    protected function buildAdminCourseManagementButtonGroup()
    {
        $buttonGroup = new ButtonGroup(array(), array('btn-group-vertical'));
        
        $buttonGroup->addButton(
            new Button(
                Translation::get('CourseCreate'), 
                new BootstrapGlyph('plus'), 
                $this->get_url(
                    array(
                        Application::PARAM_ACTION => self::ACTION_COURSE_MANAGER, 
                        \Chamilo\Application\Weblcms\Course\Manager::PARAM_ACTION => \Chamilo\Application\Weblcms\Course\Manager::ACTION_QUICK_CREATE))));
        
        $buttonGroup->addButton(
            new Button(
                Translation::get('CourseOverviewCourseList'), 
                new BootstrapGlyph('list'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_COURSE_MANAGER))));
        
        $manageDropDownButton = new DropdownButton(
            Translation::get('CourseOverviewManagement'), 
            new BootstrapGlyph('list-alt'));
        $buttonGroup->addButton($manageDropDownButton);
        
        $manageDropDownButton->addSubButton(
            new SubButton(
                Translation::get('RequestList'), 
                new BootstrapGlyph('list-alt'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_REQUEST)), 
                Button::DISPLAY_LABEL));
        
        $manageDropDownButton->addSubButton(
            new SubButton(
                Translation::get('UserRequestList'), 
                new BootstrapGlyph('list'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_ADMIN_REQUEST_BROWSER)), 
                Button::DISPLAY_LABEL));
        
        $manageDropDownButton->addSubButton(
            new SubButton(
                Translation::get('CourseCategoryManagement'), 
                new BootstrapGlyph('move'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_COURSE_CATEGORY_MANAGER)), 
                Button::DISPLAY_LABEL));
        
        $importDropDownButton = new DropdownButton(
            Translation::get('CourseOverviewImport'), 
            new BootstrapGlyph('import'));
        $buttonGroup->addButton($importDropDownButton);
        
        $importDropDownButton->addSubButton(
            new SubButton(
                Translation::get('ImportCourseCSV'), 
                new BootstrapGlyph('import'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_IMPORT_COURSES)), 
                Button::DISPLAY_LABEL));
        
        $importDropDownButton->addSubButton(
            new SubButton(
                Translation::get('ImportUsersForCourseCSV'), 
                new BootstrapGlyph('import'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_IMPORT_COURSE_USERS)), 
                Button::DISPLAY_LABEL));
        
        $buttonGroup->addButton(
            new Button(
                Translation::get('Reporting'), 
                new BootstrapGlyph('stats'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_REPORTING))));
        
        return $buttonGroup;
    }

    /**
     *
     * @return \Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup
     */
    protected function buildTeacherManagementButtonGroup()
    {
        $buttonGroup = new ButtonGroup(array(), array('btn-group-vertical'));
        
        $courseManagementRights = CourseManagementRights::getInstance();
        
        $countDirect = $countRequest = 0;
        
        $courseTypes = \Chamilo\Application\Weblcms\CourseType\Storage\DataManager::retrieve_active_course_types();
        
        while ($courseType = $courseTypes->next_result())
        {
            if ($courseManagementRights->is_allowed(
                CourseManagementRights::CREATE_COURSE_RIGHT, 
                $courseType->get_id(), 
                CourseManagementRights::TYPE_COURSE_TYPE))
            {
                $countDirect ++;
            }
            elseif ($courseManagementRights->is_allowed(
                CourseManagementRights::REQUEST_COURSE_RIGHT, 
                $courseType->get_id(), 
                CourseManagementRights::TYPE_COURSE_TYPE))
            {
                $countRequest ++;
            }
        }
        
        $allowCourseCreationWithoutCoursetype = Configuration::getInstance()->get_setting(
            array('Chamilo\Application\Weblcms', 'allow_course_creation_without_coursetype'));
        
        if ($allowCourseCreationWithoutCoursetype)
        {
            $countDirect ++;
        }
        
        if ($countDirect)
        {
            $buttonGroup->addButton(
                new Button(
                    Translation::get('CourseCreate'), 
                    new BootstrapGlyph('plus'), 
                    $this->get_url(
                        array(
                            Application::PARAM_ACTION => self::ACTION_COURSE_MANAGER, 
                            \Chamilo\Application\Weblcms\Course\Manager::PARAM_ACTION => \Chamilo\Application\Weblcms\Course\Manager::ACTION_QUICK_CREATE))));
        }
        
        if ($countRequest)
        {
            $buttonGroup->addButton(
                new Button(
                    Translation::get('CourseRequest'), 
                    new BootstrapGlyph('plus'), 
                    $this->get_url(
                        array(
                            Application::PARAM_ACTION => self::ACTION_REQUEST, 
                            \Chamilo\Application\Weblcms\Request\Manager::PARAM_ACTION => \Chamilo\Application\Weblcms\Request\Manager::ACTION_CREATE))));
        }
        
        if (\Chamilo\Application\Weblcms\Request\Rights\Rights::getInstance()->request_is_allowed())
        {
            $buttonGroup->addButton(
                new Button(
                    Translation::get('RequestList'), 
                    new BootstrapGlyph('list-alt'), 
                    $this->get_url(array(Application::PARAM_ACTION => self::ACTION_REQUEST))));
        }
        
        if (\Chamilo\Application\Weblcms\Admin\Extension\Platform\Storage\DataManager::user_is_admin($this->get_user()))
        {
            $buttonGroup->addButton(
                new Button(
                    Translation::get(
                        'TypeName', 
                        null, 
                        \Chamilo\Application\Weblcms\Admin\Extension\Platform\Manager::package()), 
                    new BootstrapGlyph('search'), 
                    $this->get_url(
                        array(
                            Application::PARAM_CONTEXT => \Chamilo\Application\Weblcms\Admin\Extension\Platform\Manager::package(), 
                            Application::PARAM_ACTION => \Chamilo\Application\Weblcms\Admin\Extension\Platform\Manager::ACTION_BROWSE))));
        }
        
        return $buttonGroup;
    }

    /**
     *
     * @return \Chamilo\Libraries\Format\Structure\ActionBar\ButtonGroup
     */
    protected function buildUserManagementButtonGroup()
    {
        $buttonGroup = new ButtonGroup(array(), array('btn-group-vertical'));
        
        $buttonGroup->addButton(
            new Button(
                Translation::get('BrowseOpenCourses'), 
                new BootstrapGlyph('list'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_BROWSE_OPEN_COURSES))));
        
        $buttonGroup->addButton(
            new Button(
                Translation::get('SortMyCourses'), 
                new BootstrapGlyph('refresh'), 
                $this->get_url(array(Application::PARAM_ACTION => self::ACTION_MANAGER_SORT))));
        
        $showSubscribeButtonOnCourseHome = Configuration::getInstance()->get_setting(
            array('Chamilo\Application\Weblcms', 'show_subscribe_button_on_course_home'));
        
        if ($showSubscribeButtonOnCourseHome)
        {
            $buttonGroup->addButton(
                new Button(
                    Translation::get('CourseSubscribe'), 
                    new BootstrapGlyph('log-in'), 
                    $this->get_url(
                        array(
                            Application::PARAM_ACTION => self::ACTION_COURSE_MANAGER, 
                            \Chamilo\Application\Weblcms\Course\Manager::PARAM_ACTION => \Chamilo\Application\Weblcms\Course\Manager::ACTION_BROWSE_UNSUBSCRIBED_COURSES))));
            
            $buttonGroup->addButton(
                new Button(
                    Translation::get('CourseUnsubscribe'), 
                    new BootstrapGlyph('log-out'), 
                    $this->get_url(
                        array(
                            Application::PARAM_ACTION => self::ACTION_COURSE_MANAGER, 
                            \Chamilo\Application\Weblcms\Course\Manager::PARAM_ACTION => \Chamilo\Application\Weblcms\Course\Manager::ACTION_BROWSE_SUBSCRIBED_COURSES))));
        }
        
        return $buttonGroup;
    }

    /**
     *
     * @return boolean
     */
    public function show_empty_courses()
    {
        return false;
    }
}
