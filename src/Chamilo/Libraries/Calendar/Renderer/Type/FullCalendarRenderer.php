<?php
namespace Chamilo\Libraries\Calendar\Renderer\Type;

use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\File\PathBuilder;

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

        $html[] = '<script>';
        $html[] = '	$(document).ready(function() {';
        $html[] = '    		$(\'#calendar\').fullCalendar({';
        $html[] = '    		    		header: {';
        $html[] = '    		    		    		left: "today prev,next title",';
        $html[] = '    		    		    		right: "month,agendaWeek,agendaDay,listWeek"';
        $html[] = '    		    		},';
        $html[] = '    		    		defaultDate: "' . date('Y-m-d', $this->getDisplayTime()) . '",';
        $html[] = '    		    		navLinks: true,';
        $html[] = '    		});';

        $html[] = '';
        $html[] = '';
        $html[] = '';

        $html[] = '	});';
        $html[] = '</script>';

        $html[] = '<div class="col-xs-12 col-lg-10 table-calendar-main">';
        $html[] = '<div id="calendar"></div>';
        $html[] = '</div>';

        $test = <<<EOT
<script>

	$(document).ready(function() {

		$('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay,listWeek'
			},
			defaultDate: '2016-12-12',
			navLinks: true, // can click day/week names to navigate views
			editable: true,
			eventLimit: true, // allow "more" link when too many events
			events: [
				{
					title: 'All Day Event',
					start: '2016-12-01'
				},
				{
					title: 'Long Event',
					start: '2016-12-07',
					end: '2016-12-10'
				},
				{
					id: 999,
					title: 'Repeating Event',
					start: '2016-12-09T16:00:00'
				},
				{
					id: 999,
					title: 'Repeating Event',
					start: '2016-12-16T16:00:00'
				},
				{
					title: 'Conference',
					start: '2016-12-11',
					end: '2016-12-13'
				},
				{
					title: 'Meeting',
					start: '2016-12-12T10:30:00',
					end: '2016-12-12T12:30:00'
				},
				{
					title: 'Lunch',
					start: '2016-12-12T12:00:00'
				},
				{
					title: 'Meeting',
					start: '2016-12-12T14:30:00'
				},
				{
					title: 'Happy Hour',
					start: '2016-12-12T17:30:00'
				},
				{
					title: 'Dinner',
					start: '2016-12-12T20:00:00'
				},
				{
					title: 'Birthday Party',
					start: '2016-12-13T07:00:00'
				},
				{
					title: 'Click for Google',
					url: 'http://google.com/',
					start: '2016-12-28'
				}
			]
		});

	});

</script>
EOT;

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
