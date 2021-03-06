<?php
namespace Chamilo\Core\Repository\ContentObject\CalendarEvent\Common\Rendition;

use Chamilo\Core\Repository\ContentObject\CalendarEvent\Common\RenditionImplementation;
use Chamilo\Core\Repository\ContentObject\CalendarEvent\Storage\DataClass\CalendarEvent;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\Utilities;

class HtmlRenditionImplementation extends RenditionImplementation
{

    public function get_string()
    {
        $object = $this->get_content_object();
        $date_format = Translation::get('DateTimeFormatLong', null, Utilities::COMMON_LIBRARIES);
        
        $prepend = array();
        
        if ($object->get_location())
        {
            $prepend[] = '<div class="calendar_event_location" style="font-weight: bold;">';
            $prepend[] = Translation::get('Location') . ': ' . $object->get_location();
            $prepend[] = '</div>';
        }
        
        $prepend[] = '<div class="calendar_event_range" style="font-weight: bold;">';
        $prepend[] = Translation::get(
            'CalendarEventDate', 
            array(
                'START' => DatetimeUtilities::format_locale_date($date_format, $object->get_start_date()), 
                'END' => DatetimeUtilities::format_locale_date($date_format, $object->get_end_date())));
        $prepend[] = '</div>';
        $repeats = $object->has_frequency();
        
        if ($repeats)
        {
            $prepend[] = '<div class="calendar_event_range" style="font-weight: bold;">';
            
            switch ($object->get_frequency())
            {
                
                case 1 :
                    if ($object->get_frequency_interval() == 1)
                    {
                        $prepend[] = Translation::get('EveryDay');
                    }
                    else
                    {
                        $prepend[] = Translation::get('EveryXDays', array('DAYS' => $object->get_frequency_interval()));
                    }
                    break;
                case 2 :
                    $days = array();
                    foreach (explode(',', $object->get_byday()) as $day)
                    {
                        $days[] = CalendarEvent::get_day_string($day);
                    }
                    if ($object->get_frequency_interval() == 1)
                    {
                        $prepend[] = Translation::get('EveryWeek', array('DAYS' => implode(', ', $days)));
                    }
                    else
                    {
                        $prepend[] = Translation::get(
                            'EveryXWeeksOnY', 
                            array('WEEKS' => $object->get_frequency_interval(), 'DAYS' => implode(', ', $days)));
                    }
                    break;
                case 5 :
                    if ($object->get_bymonthday())
                    {
                        if ($object->get_frequency_interval() == 1)
                        {
                            if (count($object->get_bymonthday()) > 1)
                            {
                                $prepend[] = Translation::get(
                                    'EveryMonthOnDaysY', 
                                    array('DAYS' => $object->get_bymonthday()));
                            }
                            else
                            {
                                $prepend[] = Translation::get(
                                    'EveryMonthOnDayY', 
                                    array('DAY' => $object->get_bymonthday()));
                            }
                        }
                        else
                        {
                            if (count($object->get_bymonthday()) > 1)
                            {
                                $prepend[] = Translation::get(
                                    'EveryXMonthsOnDaysY', 
                                    array(
                                        'MONTHS' => $object->get_frequency_interval(), 
                                        'DAYS' => $object->get_bymonthday()));
                            }
                            else
                            {
                                $prepend[] = Translation::get(
                                    'EveryXMonthsOnDayY', 
                                    array(
                                        'MONTHS' => $object->get_frequency_interval(), 
                                        'DAY' => $object->get_bymonthday()));
                            }
                        }
                    }
                    else
                    {
                        $byday = CalendarEvent::get_byday_parts($object->get_byday());
                        $byday = $byday[0];
                        if ($object->get_frequency_interval() == 1)
                        {
                            $prepend[] = Translation::get(
                                'EveryMonthOnRankDay', 
                                array(
                                    'RANK' => CalendarEvent::get_rank_string($byday[0]), 
                                    'DAY' => CalendarEvent::get_day_string($byday[1])));
                        }
                        else
                        {
                            $prepend[] = Translation::get(
                                'EveryXMonthsOnRankDay', 
                                array(
                                    'MONTHS' => $object->get_frequency_interval(), 
                                    'RANK' => CalendarEvent::get_rank_string($byday[0]), 
                                    'DAY' => CalendarEvent::get_day_string($byday[1])));
                        }
                    }
                    break;
                case 6 :
                    if ($object->get_bymonthday())
                    {
                        if ($object->get_frequency_interval() == 1)
                        {
                            if (count($object->get_bymonthday()) > 1)
                            {
                                $prepend[] = Translation::get(
                                    'EveryYearOnDaysYOfMonthZ', 
                                    array(
                                        'YEARS' => $object->get_frequency_interval(), 
                                        'DAYS' => $object->get_bymonthday(), 
                                        'MONTH' => CalendarEvent::get_bymonth_string($object->get_bymonth())));
                            }
                            else
                            {
                                $prepend[] = Translation::get(
                                    'EveryYearOnDayOfMonthZ', 
                                    array(
                                        'DAYS' => $object->get_bymonthday(), 
                                        'MONTH' => CalendarEvent::get_bymonth_string($object->get_bymonth())));
                            }
                        }
                        else
                        {
                            if (count($object->get_bymonthday()) > 1)
                            {
                                $prepend[] = Translation::get(
                                    'EveryXYearsOnDaysYOfMonthZ', 
                                    array(
                                        'YEARS' => $object->get_frequency_interval(), 
                                        'DAYS' => $object->get_bymonthday(), 
                                        'MONTH' => CalendarEvent::get_bymonth_string($object->get_bymonth())));
                            }
                            else
                            {
                                $prepend[] = Translation::get(
                                    'EveryXYearsOnDayOfMonthZ', 
                                    array(
                                        'YEARS' => $object->get_frequency_interval(), 
                                        'DAYS' => $object->get_bymonthday(), 
                                        'MONTH' => CalendarEvent::get_bymonth_string($object->get_bymonth())));
                            }
                        }
                    }
                    else
                    {
                        if ($object->get_frequency_interval() == 1)
                        {
                            $byday = CalendarEvent::get_byday_parts($object->get_byday());
                            $byday = $byday[0];
                            $prepend[] = Translation::get(
                                'EveryYearOnRankDayOfMonthZ', 
                                array(
                                    'RANK' => CalendarEvent::get_rank_string($byday[0]), 
                                    'DAY' => CalendarEvent::get_day_string($byday[1]), 
                                    'MONTH' => CalendarEvent::get_bymonth_string($object->get_bymonth())));
                        }
                        else
                        {
                            $byday = CalendarEvent::get_byday_parts($object->get_byday());
                            $byday = $byday[0];
                            $prepend[] = Translation::get(
                                'EveryXYearsOnRankDayOfMonthZ', 
                                array(
                                    'YEARS' => $object->get_frequency_interval(), 
                                    'RANK' => CalendarEvent::get_rank_string($byday[0]), 
                                    'DAY' => CalendarEvent::get_day_string($byday[1]), 
                                    'MONTH' => CalendarEvent::get_bymonth_string($object->get_bymonth())));
                        }
                    }
                    break;
            }
            $prepend[] = '</div>';
            
            if ($object->get_frequency_count() || $object->get_until())
            {
                $prepend[] = '<div class="range" style="font-weight: bold;">';
                if ($object->get_frequency_count())
                {
                    $prepend[] = Translation::get('OccursXTimes', array('TIMES' => $object->get_frequency_count()));
                }
                if ($object->get_until())
                {
                    $prepend[] = Translation::get(
                        'RepeatUntilDate', 
                        array('DATE' => DatetimeUtilities::format_locale_date($date_format, $object->get_until())));
                }
                $prepend[] = '</div>';
            }
        }
        
        return implode('', $prepend);
    }
}
