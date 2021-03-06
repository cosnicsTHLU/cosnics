<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Libraries\Calendar\Event;

use Chamilo\Application\Weblcms\Course\Storage\DataClass\Course;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Platform\Translation;

/**
 *
 * @package Chamilo\Application\Weblcms\Integration\Chamilo\Libraries\Calendar\Event
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class EventParser
{

    /**
     *
     * @var \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication
     */
    private $publication;

    /**
     *
     * @var integer
     */
    private $fromDate;

    /**
     *
     * @var integer
     */
    private $toDate;

    /**
     *
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $publication
     * @param integer $fromDate
     * @param integer $toDate
     */
    public function __construct(ContentObjectPublication $publication, $fromDate, $toDate)
    {
        $this->publication = $publication;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
     *
     * @return \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication
     */
    public function getPublication()
    {
        return $this->publication;
    }

    /**
     *
     * @param \Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication $publication
     */
    public function setPublication($publication)
    {
        $this->publication = $publication;
    }

    /**
     *
     * @return integer
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     *
     * @param integer $fromDate
     */
    public function setFromDate($fromDate)
    {
        $this->fromDate = $fromDate;
    }

    /**
     *
     * @return integer
     */
    public function getToDate()
    {
        return $this->toDate;
    }

    /**
     *
     * @param integer $toDate
     */
    public function setToDate($toDate)
    {
        $this->toDate = $toDate;
    }

    /**
     *
     * @return \Chamilo\Core\Repository\Integration\Chamilo\Libraries\Calendar\Event\Event[]
     */
    public function getEvents()
    {
        $course = \Chamilo\Application\Weblcms\Storage\DataManager::retrieve_by_id(
            Course::class_name(), 
            $this->getPublication()->get_course_id());
        
        $parser = \Chamilo\Core\Repository\Integration\Chamilo\Libraries\Calendar\Event\EventParser::factory(
            $this->getPublication()->get_content_object(), 
            $this->getFromDate(), 
            $this->getToDate(), 
            Event::class_name());
        
        $events = $parser->getEvents();
        foreach ($events as &$parsedEvent)
        {
            $parameters = array();
            $parameters[Application::PARAM_CONTEXT] = \Chamilo\Application\Weblcms\Manager::context();
            $parameters[Application::PARAM_ACTION] = \Chamilo\Application\Weblcms\Manager::ACTION_VIEW_COURSE;
            $parameters[\Chamilo\Application\Weblcms\Manager::PARAM_TOOL_ACTION] = \Chamilo\Application\Weblcms\Tool\Manager::ACTION_VIEW;
            $parameters[\Chamilo\Application\Weblcms\Manager::PARAM_COURSE] = $this->getPublication()->get_course_id();
            $parameters[\Chamilo\Application\Weblcms\Manager::PARAM_TOOL] = $this->getPublication()->get_tool();
            $parameters[\Chamilo\Application\Weblcms\Manager::PARAM_PUBLICATION] = $this->getPublication()->get_id();
            
            $redirect = new Redirect($parameters);
            $link = $redirect->getUrl();
            
            $parsedEvent->setUrl($link);
            $parsedEvent->setSource(
                Translation::get('Course', null, \Chamilo\Application\Weblcms\Manager::context()) . ' - ' .
                     $course->get_title());
            $parsedEvent->setId($this->getPublication()->get_id());
            $parsedEvent->setContext(\Chamilo\Application\Weblcms\Manager::context());
            $parsedEvent->setCourseId($this->getPublication()->get_course_id());
            
            $result[] = $parsedEvent;
        }
        
        return $result;
    }
}
