<?php
namespace Chamilo\Application\Weblcms\Course\OpenCourse\Ajax\Component;

use Chamilo\Application\Weblcms\Course\OpenCourse\Service\OpenCourseService;
use Chamilo\Application\Weblcms\Course\Storage\DataClass\Course;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\ResultSet\ResultSet;

/**
 * Returns the courses formatted for the element finder
 * 
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class GetCoursesForElementFinderComponent extends \Chamilo\Application\Weblcms\Course\Ajax\Component\GetCoursesForElementFinderComponent
{

    /**
     * Returns the number of total elements (without the offset)
     * 
     * @return int
     */
    public function getTotalNumberOfElements()
    {
        return $this->getOpenCourseService()->countClosedCourses($this->getCondition());
    }

    /**
     * Retrieves the courses for the current request
     * 
     * @return ResultSet
     */
    protected function getCourses()
    {
        return $this->getOpenCourseService()->getClosedCourses(
            $this->getCondition(), 
            $this->ajaxResultGenerator->getOffset(), 
            100, 
            array(new OrderBy(new PropertyConditionVariable(Course::class_name(), Course::PROPERTY_TITLE))));
    }

    /**
     *
     * @return OpenCourseService
     */
    public function getOpenCourseService()
    {
        return $this->getService('chamilo.application.weblcms.course.open_course.service.open_course_service');
    }
}