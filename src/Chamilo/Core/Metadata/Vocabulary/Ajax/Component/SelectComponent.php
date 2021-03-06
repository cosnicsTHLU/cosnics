<?php
namespace Chamilo\Core\Metadata\Vocabulary\Ajax\Component;

use Chamilo\Core\Metadata\Storage\DataClass\Vocabulary;
use Chamilo\Core\Metadata\Vocabulary\Table\Select\SelectTable;
use Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\Page;
use Chamilo\Libraries\Format\Table\Interfaces\TableSupport;
use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\ComparisonCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 *
 * @package Chamilo\Core\User\Ajax
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class SelectComponent extends \Chamilo\Core\Metadata\Vocabulary\Ajax\Manager implements TableSupport
{
    const PARAM_ELEMENT_ID = 'elementId';
    const PARAM_SCHEMA_ID = 'schemaId';
    const PARAM_SCHEMA_INSTANCE_ID = 'schemaInstanceId';

    /**
     *
     * @var ButtonToolBarRenderer
     */
    private $buttonToolbarRenderer;

    /**
     *
     * @var \Chamilo\Core\Metadata\Element\Storage\DataClass\Element
     */
    private $element;

    /**
     *
     * @var integer
     */
    private $elementId;

    public function run()
    {
        $elementId = $this->getPostDataValue(\Chamilo\Core\Metadata\Element\Manager::PARAM_ELEMENT_ID);
        
        if (! $this->getSelectedElementId())
        {
            throw new NoObjectSelectedException(Translation::get('Element', null, 'Chamilo\Core\Metadata\Element'));
        }
        
        if (! $this->getSelectedElement()->usesVocabulary())
        {
            throw new \Exception(Translation::get('NoVocabularyAllowed'));
        }
        
        Page::getInstance()->setViewMode(Page::VIEW_MODE_HEADERLESS);
        
        $content = $this->getContent();
        
        $html = array();
        
        $html[] = $this->render_header();
        $html[] = $content;
        $html[] = $this->render_footer();
        
        return implode(PHP_EOL, $html);
    }

    public function getContent()
    {
        $html = array();
        $vocabularyIds = $this->getSelectedVocabularyId();
        $this->buttonToolbarRenderer = $this->getButtonToolbarRenderer();
        
        if (count($vocabularyIds) > 0)
        {
            $vocabularyItems = $this->getVocabularyItems($vocabularyIds);
            $vocabularyItemValues = array();
            
            while ($vocabularyItem = $vocabularyItems->next_result())
            {
                $item = new \stdClass();
                $item->id = $vocabularyItem->get_id();
                $item->value = $vocabularyItem->get_value();
                
                $vocabularyItemValues[] = $item;
            }
            
            $resource_manager = ResourceManager::getInstance();
            $plugin_path = Path::getInstance()->getJavascriptPath('Chamilo\Core\Metadata', true) .
                 'Plugin/Bootstrap/Tagsinput/';
            
            $html[] = '<script type="text/javascript">';
            $html[] = 'var selectedVocabularyItems = ' . json_encode($vocabularyItemValues) . ';';
            $html[] = 'var elementIdentifier = ' . json_encode(
                $this->getRequest()->query->get(
                    \Chamilo\Core\Metadata\Vocabulary\Ajax\Manager::PARAM_ELEMENT_IDENTIFIER)) . ';';
            $html[] = '</script>';
            $html[] = $resource_manager->get_resource_html($plugin_path . 'bootstrap-typeahead.js');
            $html[] = $resource_manager->get_resource_html($plugin_path . 'bootstrap-tagsinput.js');
            $html[] = $resource_manager->get_resource_html(
                Path::getInstance()->getJavascriptPath('Chamilo\Core\Metadata', true) . 'Selection.js');
        }
        else
        {
            $table = new SelectTable($this);
            
            $html[] = $this->buttonToolbarRenderer->render();
            $html[] = $table->as_html();
        }
        
        return implode(PHP_EOL, $html);
    }

    /**
     * Builds the action bar
     * 
     * @return ButtonToolBarRenderer
     */
    protected function getButtonToolbarRenderer()
    {
        if (! isset($this->buttonToolbarRenderer))
        {
            $buttonToolbar = new ButtonToolBar($this->get_url());
            $this->buttonToolbarRenderer = new ButtonToolBarRenderer($buttonToolbar);
        }
        
        return $this->buttonToolbarRenderer;
    }

    /**
     * Returns the condition
     * 
     * @param string $table_class_name
     *
     * @return \libraries\storage\Condition
     */
    public function get_table_condition($table_class_name)
    {
        $conditions = array();
        
        $searchCondition = $this->buttonToolbarRenderer->getConditions(
            array(new PropertyConditionVariable(Vocabulary::class_name(), Vocabulary::PROPERTY_VALUE)));
        
        if ($searchCondition)
        {
            $conditions[] = $searchCondition;
        }
        
        $conditions[] = new ComparisonCondition(
            new PropertyConditionVariable(Vocabulary::class_name(), Vocabulary::PROPERTY_ELEMENT_ID), 
            ComparisonCondition::EQUAL, 
            new StaticConditionVariable($this->getSelectedElementId()));
        
        $conditions[] = $this->getVocabularyCondition();
        
        return new AndCondition($conditions);
    }

    public function getVocabularyCondition()
    {
        $element = $this->getSelectedElement();
        
        $userConditions = array();
        
        if ($element->isVocabularyUserDefined())
        {
            $userConditions[] = new ComparisonCondition(
                new PropertyConditionVariable(Vocabulary::class_name(), Vocabulary::PROPERTY_USER_ID), 
                ComparisonCondition::EQUAL, 
                new StaticConditionVariable($this->get_user_id()));
        }
        
        if ($element->isVocabularyPredefined())
        {
            $userConditions[] = new ComparisonCondition(
                new PropertyConditionVariable(Vocabulary::class_name(), Vocabulary::PROPERTY_USER_ID), 
                ComparisonCondition::EQUAL, 
                new StaticConditionVariable(0));
        }
        
        return new OrCondition($userConditions);
    }

    public function getVocabularyItems($vocabularyIds)
    {
        $conditions = array();
        $conditions[] = $this->getVocabularyCondition();
        $conditions[] = new InCondition(
            new PropertyConditionVariable(Vocabulary::class_name(), Vocabulary::PROPERTY_ID), 
            $vocabularyIds);
        
        $condition = new AndCondition($conditions);
        
        return DataManager::retrieves(Vocabulary::class_name(), new DataClassRetrievesParameters($condition));
    }

    /**
     *
     * @return integer
     */
    public function getSelectedElementId()
    {
        if (! isset($this->elementId))
        {
            $this->elementId = $this->getPostDataValue(\Chamilo\Core\Metadata\Element\Manager::PARAM_ELEMENT_ID);
        }
        return $this->elementId;
    }

    /**
     *
     * @return \Chamilo\Core\Metadata\Element\Storage\DataClass\Element
     */
    public function getSelectedElement()
    {
        if (! isset($this->element))
        {
            $this->element = DataManager::retrieve_by_id(
                \Chamilo\Core\Metadata\Storage\DataClass\Element::class_name(), 
                $this->getSelectedElementId());
        }
        
        return $this->element;
    }

    /**
     *
     * @return integer
     */
    public function getSelectedVocabularyId()
    {
        return (array) $this->getRequest()->get(\Chamilo\Core\Metadata\Vocabulary\Manager::PARAM_VOCABULARY_ID);
    }

    /**
     * Get an array of parameters which should be set for this call to work
     * 
     * @return array
     */
    public function getRequiredPostParameters()
    {
        return array(\Chamilo\Core\Metadata\Element\Manager::PARAM_ELEMENT_ID);
    }

    public function get_additional_parameters()
    {
        return array(
            \Chamilo\Core\Metadata\Element\Manager::PARAM_ELEMENT_ID, 
            \Chamilo\Core\Metadata\Vocabulary\Ajax\Manager::PARAM_ELEMENT_IDENTIFIER);
    }
}