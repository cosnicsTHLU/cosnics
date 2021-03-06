<?php
namespace Chamilo\Application\Calendar\Extension\Office365\Integration\Chamilo\Libraries\Calendar\Event;

use Chamilo\Application\Calendar\Storage\DataClass\AvailableCalendar;
use Chamilo\Libraries\Calendar\Event\RecurrenceRules\RecurrenceRules;
use Chamilo\Libraries\Platform\Translation;

/**
 *
 * @package Chamilo\Application\Calendar\Extension\Personal\Integration\Chamilo\Libraries\Calendar\Event
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class EventParser
{

    /**
     *
     * @var \Chamilo\Application\Calendar\Storage\DataClass\AvailableCalendar
     */
    private $availableCalendar;

    /**
     *
     * @var \stdClass
     */
    private $office365CalendarEvent;

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
     * @param \Chamilo\Application\Calendar\Storage\DataClass\AvailableCalendar $availableCalendar
     * @param \Office365_Service_Calendar_Event $office365CalendarEvent
     * @param integer $fromDate
     * @param integer $toDate
     */
    public function __construct(AvailableCalendar $availableCalendar, \stdClass $office365CalendarEvent, $fromDate, 
        $toDate)
    {
        $this->availableCalendar = $availableCalendar;
        $this->office365CalendarEvent = $office365CalendarEvent;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
     *
     * @return \Chamilo\Application\Calendar\Storage\DataClass\AvailableCalendar
     */
    public function getAvailableCalendar()
    {
        return $this->availableCalendar;
    }

    /**
     *
     * @param \Chamilo\Application\Calendar\Storage\DataClass\AvailableCalendar $availableCalendar
     */
    public function setAvailableCalendar(
        \Chamilo\Application\Calendar\Storage\DataClass\AvailableCalendar $availableCalendar)
    {
        $this->availableCalendar = $availableCalendar;
    }

    /**
     *
     * @return \stdClass
     */
    public function getOffice365CalendarEvent()
    {
        return $this->office365CalendarEvent;
    }

    /**
     *
     * @param \stdClass $office365CalendarEvent
     */
    public function setOffice365CalendarEvent($office365CalendarEvent)
    {
        $this->office365CalendarEvent = $office365CalendarEvent;
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
        $office365CalendarEvent = $this->getOffice365CalendarEvent();
        
        $url = null;
        
        $event = new Event(
            $office365CalendarEvent->Id, 
            $this->getTimestamp(
                $office365CalendarEvent->Start, 
                $office365CalendarEvent->StartTimeZone, 
                $office365CalendarEvent->IsAllDay), 
            $this->getTimestamp(
                $office365CalendarEvent->End, 
                $office365CalendarEvent->EndTimeZone, 
                $office365CalendarEvent->IsAllDay), 
            $this->getRecurrence($office365CalendarEvent->Recurrence), 
            $url, 
            $office365CalendarEvent->Subject, 
            $office365CalendarEvent->Body->Content, 
            $office365CalendarEvent->location->displayName, 
            $this->getSource($this->getAvailableCalendar()->getName()), 
            \Chamilo\Application\Calendar\Extension\Office365\Manager::context());
        
        $event->setOffice365CalendarEvent($office365CalendarEvent);
        
        return array($event);
    }

    /**
     *
     * @param string $eventDateTime
     * @param string $eventTimeZone
     */
    private function getTimestamp($eventDateTime, $eventTimeZone, $isAllDay)
    {
        $dateTime = new \DateTime($eventDateTime, $this->determineTimeZone($eventTimeZone, $isAllDay));
        
        if ($isAllDay)
        {
            return mktime(0, 0, 0, $dateTime->format('n'), $dateTime->format('j'), $dateTime->format('Y'));
        }
        
        return $dateTime->getTimestamp();
    }

    /**
     *
     * @param string $eventTimeZone
     * @param boolean $isAllDay
     * @return \DateTimeZone|NULL
     */
    private function determineTimeZone($eventTimeZone)
    {
        if ($eventTimeZone)
        {
            try
            {
                return new \DateTimeZone($eventTimeZone);
            }
            catch (\Exception $exception)
            {
                return null;
            }
        }
        else
        {
            return null;
        }
    }

    /**
     *
     * @param string $calendarName
     * @return string
     */
    private function getSource($calendarName)
    {
        return Translation::get(
            'SourceName', 
            array('CALENDAR' => $calendarName), 
            \Chamilo\Application\Calendar\Extension\Office365\Manager::context());
    }

    /**
     *
     * @param string[] $recurrenceRules
     * @return \Chamilo\Libraries\Calendar\Event\RecurrenceRules
     */
    private function getRecurrence(\stdClass $recurrence = null)
    {
        $recurrenceRules = new RecurrenceRules();
        
        if ($recurrence instanceof \stdClass)
        {
            $recurrenceRules->setFrequency($this->getFrequency($recurrence->Pattern->Type));
            
            if ($recurrence->Pattern->Interval > 0)
            {
                $recurrenceRules->setInterval((string) $recurrence->Pattern->Interval);
            }
            
            if ($recurrence->Range->Type == 'Numbered')
            {
                $recurrenceRules->setCount((string) $recurrence->Range->NumberOfOccurrences);
            }
            
            if ($recurrence->Range->Type == 'EndDate')
            {
                $recurrenceRules->setUntil($this->getTimestamp($recurrence->Range->EndDate));
            }
            
            if ($recurrence->Pattern->DayOfMonth != 0)
            {
                $recurrenceRules->setByMonthDay(array($recurrence->Pattern->DayOfMonth));
            }
            
            if ($recurrence->Pattern->Month != 0)
            {
                $recurrenceRules->setByMonth(array($recurrence->Pattern->Month));
            }
            
            if (count($recurrence->Pattern->DaysOfWeek) > 0)
            {
                $recurrenceRules->setByDay(
                    $this->getByDay(
                        $recurrence->Pattern->Type, 
                        $recurrence->Pattern->Index, 
                        $recurrence->Pattern->DaysOfWeek));
            }
        }
        
        return $recurrenceRules;
    }

    /**
     *
     * @param string[] $daysOfWeek
     * @return string[]
     */
    private function getByDay($patternType, $patternIndex, $patternDaysOfWeek)
    {
        $byDay = array();
        
        $prefix = $this->getNumericIndex($patternType, $patternIndex);
        
        foreach ($patternDaysOfWeek as $dayOfWeek)
        {
            $byDay[] = $prefix . substr(strtoupper($dayOfWeek), 0, 2);
        }
        
        return $byDay;
    }

    /**
     *
     * @param string $patternType
     * @param unknown $patternIndex
     * @return string
     */
    private function getNumericIndex($patternType, $patternIndex)
    {
        if (! in_array($patternType, array('RelativeMonthly', 'RelativeYearly')))
        {
            return '';
        }
        
        switch ($patternIndex)
        {
            case 'First' :
                return '1';
                break;
            case 'Second' :
                return '2';
                break;
            case 'Third' :
                return '3';
                break;
            case 'Fourth' :
                return '4';
                break;
            case 'Last' :
                return '-1';
                break;
            default :
                return '';
        }
    }

    /**
     *
     * @param string $frequencyType
     * @return integer
     */
    private function getFrequency($frequencyType)
    {
        switch ($frequencyType)
        {
            case 'Daily' :
                return RecurrenceRules::FREQUENCY_DAILY;
                break;
            case 'Weekly' :
                return RecurrenceRules::FREQUENCY_WEEKLY;
                break;
            case 'AbsoluteMonthly' :
            case 'RelativeMonthly' :
                return RecurrenceRules::FREQUENCY_MONTHLY;
                break;
            case 'AbsoluteYearly' :
            case 'RelativeYearly' :
                return RecurrenceRules::FREQUENCY_YEARLY;
                break;
            default :
                return RecurrenceRules::FREQUENCY_NONE;
        }
    }
}
