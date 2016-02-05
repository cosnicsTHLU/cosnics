<?php
namespace Chamilo\Libraries\Utilities;

use Chamilo\Libraries\Platform\Configuration\LocalSetting;
use Chamilo\Libraries\Platform\Translation;
use DateTime;
use DateTimeZone;

/**
 * $Id: datetime_utilities.class.php 128 2009-11-09 13:13:20Z vanpouckesven $
 *
 * @package common.datetime
 */
class DatetimeUtilities
{

    /**
     * Get a four digit year from a two digit year.
     * The century depends on the year difference between the given year
     * and the current year. e.g. with default $years_difference_for_century value of 20: - calling the function in 2009
     * with a given $year value of 19 return 2019 - calling the function in 2009 with a given $year value of 75 return
     * 1975
     *
     * @param $years_difference_for_century The maximum difference of years between the current year and the given year
     *        to return the current century
     * @return integer A year number
     */
    public static function get_complete_year($year, $years_difference_for_century = 20)
    {
        if (is_numeric($year))
        {
            if ($year > 100)
            {
                return $year;
            }
            else
            {
                if ($year <= date('y') || $year - date('y') < $years_difference_for_century)
                {
                    return (date('Y') - date('y') + $year);
                }
                else
                {
                    return (date('Y') - date('y') - 100 + $year);
                }
            }
        }
        else
        {
            return null;
        }
    }

    /**
     * formats the date according to the locale settings
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Christophe Gesch� <gesche@ipm.ucl.ac.be> originally inspired from from PhpMyAdmin
     * @param string $formatOfDate date pattern
     * @param integer $timestamp, default is NOW.
     * @return the formatted date
     */
    public static function format_locale_date($dateFormat = null, $timeStamp = -1)
    {
        if (! $dateFormat)
        {
            $dateFormat = self :: default_date_time_format();
        }

        $DaysShort = self :: get_days_short(); // Defining the shorts for the days
        $DaysLong = self :: get_days_long(); // Defining the days of the week to allow translation of the days
        $MonthsShort = self :: get_month_short(); // Defining the shorts for the months
        $MonthsLong = self :: get_month_long(); // Defining the months of the year to allow translation of the months
                                                // with the ereg we replace %aAbB of date format
                                                // (they can be done by the system when locale date aren't aivailable

        $date = preg_replace('/%[A]/', $DaysLong[(int) strftime('%w', $timeStamp)], $dateFormat);
        $date = preg_replace('/%[a]/', $DaysShort[(int) strftime('%w', $timeStamp)], $date);
        $date = preg_replace('/%[B]/', $MonthsLong[(int) strftime('%m', $timeStamp) - 1], $date);
        $date = preg_replace('/%[b]/', $MonthsShort[(int) strftime('%m', $timeStamp) - 1], $date);

        if ($timeStamp == - 1)
        {
            $timeStamp = time();
        }

        return strftime($date, $timeStamp);
    }

    private static function default_date_time_format()
    {
        $translator = Translation :: getInstance();
        $short_date = $translator->getTranslation('DateFormatShort', null, Utilities :: COMMON_LIBRARIES);
        $time = $translator->getTranslation('TimeNoSecFormat', null, Utilities :: COMMON_LIBRARIES);
        $dateFormat = "{$short_date},  {$time}";
        return $dateFormat;
    }

    /**
     * Convert the given date to the selected timezone
     *
     * @param String $date The date
     * @param String $timezone The selected timezone
     */
    public static function convert_date_to_timezone($date, $format = null, $timezone = null)
    {
        if (! $format)
        {
            $format = self :: default_date_time_format();
        }

        if (! $timezone)
        {
            $timezone = LocalSetting :: getInstance()->get('platform_timezone');
            if (! $timezone)
            {
                return self :: format_locale_date($format, $date);
            }
        }

        $date_time_zone = new DateTimeZone($timezone);
        $gmt_time_zone = new DateTimeZone('GMT');

        $date_time = new DateTime($date, $gmt_time_zone);
        $offset = $date_time_zone->getOffset($date_time);

        return self :: format_locale_date($format, $date_time->format('U') + $offset);
    }

    /**
     * Convert the seconds to h:m:s or m:s or s
     *
     * @param String $time
     */
    public static function convert_seconds_to_hours($time)
    {
        if ($time / 3600 < 1 && $time / 60 < 1)
        {
            $converted_time = '000h 00m ' . str_pad($time, 2, '0', STR_PAD_LEFT) . 's';
        }
        else
        {
            if ($time / 3600 < 1)
            {
                $min = (int) ($time / 60);
                $sec = $time % 60;
                $converted_time = '000h ' . str_pad($min, 2, '0', STR_PAD_LEFT) . 'm ' .
                     str_pad($sec, 2, '0', STR_PAD_LEFT) . 's';
            }
            else
            {
                $hour = (int) ($time / 3600);
                $rest = $time % 3600;
                $min = (int) ($rest / 60);
                $sec = $rest % 60;
                $converted_time = str_pad($hour, 3, '0', STR_PAD_LEFT) . 'h ' . str_pad($min, 2, '0', STR_PAD_LEFT) .
                     'm ' . str_pad($sec, 2, '0', STR_PAD_LEFT) . 's';
            }
        }
        return $converted_time;
    }

    /**
     * Defining the shorts for the days.
     * Memoized.
     *
     * @return array
     */
    public static function get_days_short()
    {
        static $result = false;
        if ($result)
        {
            return $result;
        }

        $translator = Translation :: getInstance();

        return $result = array(
            $translator->getTranslation('SundayShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('MondayShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('TuesdayShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('WednesdayShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('ThursdayShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('FridayShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('SaturdayShort', null, Utilities :: COMMON_LIBRARIES));
    }

    /**
     * Defining the days of the week to allow translation of the days.
     * Memoized.
     *
     * @return array
     */
    public static function get_days_long()
    {
        static $result = false;
        if ($result)
        {
            return $result;
        }
        $translator = Translation :: getInstance();

        return $result = array(
            $translator->getTranslation('SundayLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('MondayLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('TuesdayLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('WednesdayLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('ThursdayLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('FridayLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('SaturdayLong', null, Utilities :: COMMON_LIBRARIES));
    }

    /**
     * Defining the shorts for the months.
     * Memoized.
     *
     * @return array
     */
    public static function get_month_short()
    {
        static $result = false;
        if ($result)
        {
            return $result;
        }

        $translator = Translation :: getInstance();

        return $result = array(
            $translator->getTranslation('JanuaryShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('FebruaryShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('MarchShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('AprilShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('MayShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('JuneShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('JulyShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('AugustShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('SeptemberShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('OctoberShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('NovemberShort', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('DecemberShort', null, Utilities :: COMMON_LIBRARIES));
    }

    /**
     * Defining the shorts for the months.
     * Memoized.
     *
     * @return array
     */
    public static function get_month_long()
    {
        static $result = false;
        if ($result)
        {
            return $result;
        }

        $translator = Translation :: getInstance();

        return $result = array(
            $translator->getTranslation('JanuaryLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('FebruaryLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('MarchLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('AprilLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('MayLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('JuneLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('JulyLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('AugustLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('SeptemberLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('OctoberLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('NovemberLong', null, Utilities :: COMMON_LIBRARIES),
            $translator->getTranslation('DecemberLong', null, Utilities :: COMMON_LIBRARIES));
    }

    /**
     * Converts a date/time value retrieved from a FormValidator datepicker element to the corresponding UNIX itmestamp.
     *
     * @param $string string The date/time value.
     * @return int The UNIX timestamp.
     */
    public static function time_from_datepicker($string)
    {
        list($date, $time) = split(' ', $string);
        list($year, $month, $day) = split('-', $date);
        list($hours, $minutes, $seconds) = split(':', $time);
        return mktime($hours, $minutes, $seconds, $month, $day, $year);
    }

    /**
     * Converts a date/time value retrieved from a FormValidator datepicker without timepicker element to the
     * corresponding UNIX itmestamp.
     *
     * @param $string string The date/time value.
     * @return int The UNIX timestamp.
     */
    public static function time_from_datepicker_without_timepicker($string, $h = 0, $m = 0, $s = 0)
    {
        list($year, $month, $day) = split('-', $string);
        return mktime($h, $m, $s, $month, $day, $year);
    }

    public static function format_seconds_to_hours($seconds)
    {
        $hours = floor($seconds / 3600);
        $rest = $seconds % 3600;

        $minutes = floor($rest / 60);
        $seconds = $rest % 60;

        if ($minutes < 10)
        {
            $minutes = '0' . $minutes;
        }

        if ($seconds < 10)
        {
            $seconds = '0' . $seconds;
        }

        return $hours . ':' . $minutes . ':' . $seconds;
    }

    public static function format_seconds_to_minutes($seconds)
    {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        if ($minutes < 10)
        {
            $minutes = '0' . $minutes;
        }

        if ($seconds < 10)
        {
            $seconds = '0' . $seconds;
        }

        return $minutes . ':' . $seconds;
    }
}
