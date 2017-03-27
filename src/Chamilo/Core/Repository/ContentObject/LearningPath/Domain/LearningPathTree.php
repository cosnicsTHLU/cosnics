<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Domain;

/**
 * Full in memory tree of a learning path. This class can be used to traverse through the nested tree in memory
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class LearningPathTree
{
    /**
     * @var LearningPathTreeNode[]
     */
    protected $learningPathTreeNodes;

    /**
     * @return LearningPathTreeNode[]
     */
    public function getLearningPathTreeNodes()
    {
        return $this->learningPathTreeNodes;
    }

    /**
     * @param LearningPathTreeNode[] $learningPathTreeNodes
     */
    public function setLearningPathTreeNodes($learningPathTreeNodes)
    {
        $this->learningPathTreeNodes = $learningPathTreeNodes;
    }

    /**
     * Adds a LearningPathTreeNode to the tree. This function is automatically called from the constructor of
     * LearningPathTreeNode and should not be called by others. Adding the same node twice throws an exception
     *
     * @param LearningPathTreeNode $learningPathTreeNode
     */
    public function addLearningPathTreeNode(LearningPathTreeNode $learningPathTreeNode)
    {
        if (in_array($learningPathTreeNode, $this->learningPathTreeNodes))
        {
            throw new \InvalidArgumentException(
                'The given learning path three node is already added to the list of nodes'
            );
        }

        $learningPathTreeNode->setStep($this->getNextStep());
        $this->learningPathTreeNodes[$learningPathTreeNode->getId()] = $learningPathTreeNode;
    }

    /**
     * @param int $id
     *
     * @return LearningPathTreeNode
     */
    public function getLearningPathTreeNodeById($id)
    {
        if (!is_integer($id))
        {
            throw new \InvalidArgumentException('The given id should be valid integer');
        }

        if (!array_key_exists($id, $this->learningPathTreeNodes))
        {
            throw new \InvalidArgumentException(
                sprintf('The learning path three node with id %s could not be found', $id)
            );
        }

        return $this->learningPathTreeNodes[$id];
    }

    /**
     * Returns the last step number
     *
     * @return int
     */
    public function getLastStepNumber()
    {
        return count($this->learningPathTreeNodes);
    }

    /**
     * Returns the root node for the learning path
     *
     * @return LearningPathTreeNode
     */
    public function getRoot()
    {
        if (empty($this->learningPathTreeNodes))
        {
            throw new \RuntimeException(
                'The learning path tree does not have a root node yet. Please use the ' .
                'LearningPathTreeBuilder service to populate the learning path tree'
            );
        }

        return $this->learningPathTreeNodes[0];
    }

    /**
     * Determines the step number for a given content object. The content object is identified with his id
     * combined with every parent content object id to determine a unique path.
     *
     * @param array $parentContentObjectIds
     *
     * @return LearningPathTreeNode
     */
    public function getLearningPathTreeNodeForContentObjectIdentifiedByParentContentObjects(
        $parentContentObjectIds = array()
    )
    {
        if ($this->getRoot()->getContentObject()->getId() != array_shift($parentContentObjectIds))
        {
            throw new \RuntimeException('The root id\'s for the current learning path are no longer matching');
        }

        $parentNode = $this->getRoot();

        foreach ($parentContentObjectIds as $parentContentObjectId)
        {
            $childFound = false;

            foreach ($parentNode->getChildNodes() as $childNode)
            {
                if ($childNode->getContentObject()->getId() == $parentContentObjectId)
                {
                    $parentNode = $childNode;
                    $childFound = true;
                    break;
                }
            }

            if (!$childFound)
            {
                throw new \RuntimeException(
                    sprintf(
                        'The system could not find a valid path in the tree for learning path %s with path (%s)',
                        $this->getRoot()->getContentObject()->getId(),
                        implode(' ,', $parentContentObjectIds)
                    )
                );
            }
        }

        return $parentNode;
    }

    /**
     * Returns the next step number for a LearningPathTreeNode
     *
     * @return int
     */
    protected function getNextStep()
    {
        return $this->getLastStepNumber() + 1;
    }
}