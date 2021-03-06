<?php
namespace Chamilo\Core\Repository\ContentObject\ExternalCalendar\Integration\Chamilo\Application\Calendar\Extension\Personal\Repository;

use Chamilo\Core\Repository\ContentObject\ExternalCalendar\Storage\DataClass\ExternalCalendar;
use Chamilo\Libraries\Storage\Query\Condition\Condition;

/**
 * Retrieves personal calendar publications for this specific content object type
 * 
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class PersonalCalendarEventDataProviderRepository extends \Chamilo\Application\Calendar\Extension\Personal\Integration\Chamilo\Application\Calendar\Repository\PersonalCalendarEventDataProviderRepository
{

    /**
     * Returns the class name for the content object to be joined with
     * 
     * @return string
     */
    protected function getContentObjectClassName()
    {
        return ExternalCalendar::class_name();
    }

    /**
     * Returns the condition for the content object
     * 
     * @param int $fromDate
     * @param int $toDate
     *
     * @return Condition
     */
    protected function getContentObjectCondition($fromDate, $toDate)
    {
        return null;
    }
}