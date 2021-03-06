<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\CourseGroup\Table\Overview\GroupUser;

use Chamilo\Application\Weblcms\Tool\Implementation\CourseGroup\Manager;
use Chamilo\Application\Weblcms\Tool\Implementation\CourseGroup\Storage\DataClass\CourseGroupUserRelation;
use Chamilo\Libraries\Format\Table\Extension\RecordTable\RecordTableCellRenderer;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;

class CourseGroupUserTableCellRenderer extends RecordTableCellRenderer
{

    /**
     * Renders a given cell.
     * 
     * @param type $column
     * @param type $user_with_subscription_status User from the advanced join query in weblcms database class that
     *        includes his subscription status.
     * @return type
     */
    public function render_cell($column, $user)
    {
        switch ($column->get_name())
        {
            case CourseGroupUserRelation::PROPERTY_SUBSCRIPTION_TIME :
                $subscriptionTime = $user[CourseGroupUserRelation::PROPERTY_SUBSCRIPTION_TIME];
                
                if ($subscriptionTime)
                {
                    return DatetimeUtilities::format_locale_date(
                        Translation::getInstance()->getTranslation('SubscriptionTimeFormat', null, Manager::context()), 
                        $subscriptionTime);
                }
                
                return null;
        }
        
        return parent::render_cell($column, $user);
    }
}
