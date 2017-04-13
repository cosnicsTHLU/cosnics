<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Display\Table\Progress;

use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\LearningPathTree;
use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\LearningPathTreeNode;
use Chamilo\Libraries\Format\Table\TableDataProvider;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\ResultSet\ArrayResultSet;
use Chamilo\Libraries\Storage\ResultSet\ResultSet;

/**
 * Shows the progress of some tree nodes for a user in the learning path
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class ProgressTableDataProvider extends TableDataProvider
{
    /**
     * @var array
     */
    protected $data;

    /**
     * Returns the data as a resultset
     *
     * @param Condition $condition
     * @param int $offset
     * @param int $count
     * @param OrderBy[] $order_property
     *
     * @return ResultSet
     */
    public function retrieve_data($condition, $offset, $count, $order_property = null)
    {
        return new ArrayResultSet(array_slice($this->getAllData(), $offset, $count));
    }

    /**
     * Counts the data
     *
     * @param Condition $condition
     *
     * @return int
     */
    public function count_data($condition)
    {
        return count($this->getAllData());
    }

    /**
     * Retrieves, caches and returns the data
     *
     * @return array
     */
    protected function getAllData()
    {
        if (!isset($this->data))
        {
            /** @var LearningPathTreeNode $learningPathTreeNode */
            $learningPathTreeNode = $this->get_component()->getCurrentLearningPathTreeNode();

            $this->data = array_values($learningPathTreeNode->getChildNodes());
        }

        return $this->data;
    }
}