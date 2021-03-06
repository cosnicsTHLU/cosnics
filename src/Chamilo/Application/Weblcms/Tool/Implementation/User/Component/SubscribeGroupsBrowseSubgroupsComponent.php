<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\User\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\User\Component\UnsubscribedGroup\UnsubscribedGroupTable;
use Chamilo\Core\Group\Storage\DataClass\Group;
use Chamilo\Libraries\Format\Table\Interfaces\TableSupport;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Condition\PatternMatchCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * $Id: user_group_subscribe_browser.class.php 216 2009-11-13 14:08:06Z kariboe $
 *
 * @package application.lib.weblcms.tool.user.component
 */
class SubscribeGroupsBrowseSubgroupsComponent extends SubscribeGroupsTabComponent implements TableSupport
{

    /**
     * Renders the content for the tab
     *
     * @return string
     */
    protected function renderTabContent()
    {
        $table = new UnsubscribedGroupTable($this);
        $table->setSearchForm($this->tabButtonToolbarRenderer->getSearchForm());

        $html = array();
        $html[] = $this->tabButtonToolbarRenderer->render();
        $html[] = $table->as_html();
        return implode(PHP_EOL, $html);
    }

    /**
     * Returns the condition for the table
     *
     * @param string $table_class_name
     *
     * @return Condition
     */
    public function get_table_condition($table_class_name)
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_PARENT_ID),
            new StaticConditionVariable($this->getGroupId()));

        $query = $this->tabButtonToolbarRenderer->getSearchForm()->getQuery();
        if (isset($query) && $query != '')
        {
            $conditions2[] = new PatternMatchCondition(
                new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_NAME),
                '*' . $query . '*');
            $conditions2[] = new PatternMatchCondition(
                new PropertyConditionVariable(Group::class_name(), Group::PROPERTY_DESCRIPTION),
                '*' . $query . '*');
            $conditions[] = new OrCondition($conditions2);
        }

        return new AndCondition($conditions);
    }
}
