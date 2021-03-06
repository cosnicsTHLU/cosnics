<?php
namespace Chamilo\Configuration\Form;

use Chamilo\Configuration\Form\Storage\DataClass\Element;
use Chamilo\Configuration\Form\Storage\DataClass\Instance;
use Chamilo\Configuration\Form\Storage\DataClass\Value;
use Chamilo\Configuration\Form\Storage\DataManager;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\SubselectCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Storage\ResultSet\ArrayResultSet;
use Chamilo\Libraries\Utilities\StringUtilities;
use HTML_Table;

/**
 *
 * @package configuration\form
 * @author Sven Vanpoucke <sven.vanpoucke@hogent.be>
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class Viewer
{

    private $context;

    private $name;

    private $user_id;

    private $title;

    public function __construct($context, $name, $user_id, $title = null)
    {
        $this->context = $context;
        $this->name = $name;
        $this->user_id = $user_id;
        $this->title = $title ? $title : Translation::get(
            (string) StringUtilities::getInstance()->createString($name)->upperCamelize(),
            $context);
    }

    public function render()
    {
        $values = $this->get_form_values();

        if ($values->size() != 0)
        {

            $table = new HTML_Table(array('class' => 'table table-striped table-bordered table-hover table-data'));
            $table->setHeaderContents(0, 0, $this->title);
            $table->setCellAttributes(0, 0, array('colspan' => 2, 'style' => 'text-align: center;'));
            $table->altRowAttributes(0, array('class' => 'row_odd'), array('class' => 'row_even'), true);

            $counter = 1;

            while ($value = $values->next_result())
            {
                $condition = new EqualityCondition(
                    new PropertyConditionVariable(Element::class_name(), Element::PROPERTY_ID),
                    new StaticConditionVariable($value->get_dynamic_form_element_id()));
                $element = DataManager::retrieve_dynamic_form_elements($condition)->next_result();

                $table->setCellContents($counter, 0, $element->get_name());
                $table->setCellAttributes($counter, 0, array('style' => 'width: 150px;'));

                $table->setCellContents($counter, 1, $value->get_value());

                $counter ++;
            }

            return $table->toHtml();
        }
    }

    public function get_form_values()
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Instance::class_name(), Instance::PROPERTY_APPLICATION),
            new StaticConditionVariable($this->context));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Instance::class_name(), Instance::PROPERTY_NAME),
            new StaticConditionVariable($this->name));
        $condition = new AndCondition($conditions);

        $form = DataManager::retrieve(Instance::class_name(), new DataClassRetrieveParameters($condition));

        if (! $form)
        {
            return new ArrayResultSet(array());
        }

        $subcondition = new EqualityCondition(
            new PropertyConditionVariable(Element::class_name(), Element::PROPERTY_DYNAMIC_FORM_ID),
            new StaticConditionVariable($form->get_id()));

        $conditions = array();
        $conditions[] = new SubselectCondition(
            new PropertyConditionVariable(Value::class_name(), Value::PROPERTY_DYNAMIC_FORM_ELEMENT_ID),
            new PropertyConditionVariable(Element::class_name(), Element::PROPERTY_ID),
            null,
            $subcondition);
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Value::class_name(), Value::PROPERTY_USER_ID),
            new StaticConditionVariable($this->user_id));
        $condition = new AndCondition($conditions);

        return DataManager::retrieves(Value::class_name(), new DataClassRetrievesParameters($condition));
    }
}
