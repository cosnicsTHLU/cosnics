<?php
namespace Chamilo\Libraries\Calendar\Renderer\Type;

use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\File\PathBuilder;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Architecture\Application\Application;

/**
 *
 * @package Chamilo\Libraries\Calendar\Renderer\Type
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class FullCalendarRenderer
{
    // Parameters
    const PARAM_TIME = 'time';
    const PARAM_TYPE = 'type';

    // Types
    const TYPE_DAY = 'Day';
    const TYPE_LIST = 'List';
    const TYPE_MONTH = 'Month';
    const TYPE_WEEK = 'Week';

    private $displayTime;

    /**
     *
     * @param integer $displayTime
     */
    public function __construct($displayTime)
    {
        $this->displayTime = $displayTime;
    }

    /**
     *
     * @return integer
     */
    public function getDisplayTime()
    {
        return $this->displayTime;
    }

    /**
     *
     * @see \Chamilo\Libraries\Calendar\Renderer\Renderer::render()
     */
    public function render()
    {
        $html = array();

        $html[] = $this->getDependencies();

        $ajaxUrl = new Redirect(
            array(
                Application::PARAM_CONTEXT => 'Ehb\Application\Calendar\Extension\SyllabusPlus\Ajax',
                Application::PARAM_ACTION => 'FullCalendarEvents'));

        $html[] = '<script>';
        $html[] = '	$(document).ready(function() {';
        $html[] = '    		$(\'#calendar\').fullCalendar({';
        $html[] = '    		    		header: {';
        $html[] = '    		    		    		left: "today prev,next title",';
        $html[] = '    		    		    		right: "month,agendaWeek,agendaDay,listWeek"';
        $html[] = '    		    		},';
        $html[] = '    		    		defaultDate: "' . date('Y-m-d', $this->getDisplayTime()) . '",';
        $html[] = '    		    		navLinks: true,
                           height: "auto",
                           firstDay: 1,
                           timeFormat: "hh:mm",
                           businessHours: {
                               dow: [ 1, 2, 3, 4, 5 ],
                               start: "10:00",
                               end: "18:00"
                           },
                           			events: {
                               				url: '. json_encode($ajaxUrl->getUrl()) .',
                               				error: function() {
                               					    $("#script-warning").show();
                               				}
                           			},
                           			loading: function(bool) {
                               				$("#loading").toggle(bool);
                           			}';
        $html[] = '    		});';

        $html[] = '	});';
        $html[] = '</script>';

        $html[] = '<div class="col-xs-12 col-lg-10 table-calendar-main">';
        $html[] = '<div id="loading">Loading...</div>';
        $html[] = '<div id="calendar"></div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    protected function getDependencies()
    {
        $html = array();

        $html[] = ResourceManager::getInstance()->get_resource_html(
            PathBuilder::getInstance()->getJavascriptPath('Chamilo\Libraries\Calendar', true) .
                 'fullcalendar/lib/moment.min.js');
        $html[] = ResourceManager::getInstance()->get_resource_html(
            PathBuilder::getInstance()->getJavascriptPath('Chamilo\Libraries\Calendar', true) .
                 'fullcalendar/fullcalendar.min.js');

        $html[] = ResourceManager::getInstance()->get_resource_html(
            PathBuilder::getInstance()->getJavascriptPath('Chamilo\Libraries\Calendar', true) .
                 'fullcalendar/fullcalendar.min.css');

        return implode(PHP_EOL, $html);
    }
}
