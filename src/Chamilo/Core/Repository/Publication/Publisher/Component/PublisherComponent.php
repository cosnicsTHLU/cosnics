<?php
namespace Chamilo\Core\Repository\Publication\Publisher\Component;

use Chamilo\Core\Repository\Publication\Publisher\Interfaces\PublisherSupport;
use Chamilo\Core\Repository\Publication\Publisher\Manager;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Format\Structure\WizardHeader\WizardHeader;
use Chamilo\Libraries\Format\Structure\WizardHeader\WizardHeaderRenderer;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;

/**
 * Publisher component that executes the repo viewer, calls the publication form and executes the publisher handler
 * 
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class PublisherComponent extends Manager implements \Chamilo\Core\Repository\Viewer\ViewerInterface, DelegateComponent
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        if (! $this->getParentApplication() instanceof PublisherSupport)
        {
            throw new \RuntimeException(
                'To use the publisher application the parent application must implement the ' .
                     '"Chamilo\Core\Repository\Publication\Publisher\Interfaces\PublisherSupport" interface');
        }
        
        if (! \Chamilo\Core\Repository\Viewer\Manager::any_object_selected())
        {
            $applicationConfiguration = new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this);
            
            $tabsSetting = $this->getApplicationConfiguration()->get(
                \Chamilo\Core\Repository\Viewer\Manager::SETTING_TABS_DISABLED);
            if (isset($tabsSetting))
            {
                $applicationConfiguration->set(
                    \Chamilo\Core\Repository\Viewer\Manager::SETTING_TABS_DISABLED, 
                    $tabsSetting);
            }
            
            $applicationConfiguration->set(\Chamilo\Core\Repository\Viewer\Manager::SETTING_BREADCRUMBS_DISABLED, true);
            
            $factory = new ApplicationFactory(
                \Chamilo\Core\Repository\Viewer\Manager::context(), 
                $applicationConfiguration);
            
            return $factory->run();
        }
        
        $selectedContentObjects = $this->getSelectedContentObjects();
        $form = $this->getPublicationForm($selectedContentObjects);
        if ($form instanceof FormValidator)
        {
            if ($form->validate())
            {
                $this->getPublicationHandler()->publish($selectedContentObjects);
            }
            else
            {
                $html = array();
                
                $html[] = $this->render_header();
                $html[] = $form->toHtml();
                $html[] = $this->render_footer();
                
                return implode(PHP_EOL, $html);
            }
        }
        else
        {
            $this->getPublicationHandler()->publish($selectedContentObjects);
        }
    }

    /**
     * Returns a list of selected content objects
     */
    protected function getSelectedContentObjects()
    {
        $selectedContentObjectIds = \Chamilo\Core\Repository\Viewer\Manager::get_selected_objects();
        
        if (! empty($selectedContentObjectIds) && ! is_array($selectedContentObjectIds))
        {
            $selectedContentObjectIds = array($selectedContentObjectIds);
        }
        
        if (count($selectedContentObjectIds) > 0)
        {
            $condition = new InCondition(
                new PropertyConditionVariable(ContentObject::class_name(), ContentObject::PROPERTY_ID), 
                $selectedContentObjectIds);
            $parameters = new DataClassRetrievesParameters($condition);
            
            return \Chamilo\Core\Repository\Storage\DataManager::retrieve_active_content_objects(
                ContentObject::class_name(), 
                $parameters)->as_array();
        }
        
        return array();
    }

    /**
     * Overwrite render header to add the wizard
     * 
     * @return string
     */
    public function render_header()
    {
        $html = array();
        $html[] = parent::render_header();
        
        $wizardHeader = new WizardHeader();
        $wizardHeader->setStepTitles(
            array($this->getWizardFirstStepTitle(), $this->getTranslation('SecondStepPublish')));
        
        $selectedStepIndex = \Chamilo\Core\Repository\Viewer\Manager::any_object_selected() ? 1 : 0;
        $wizardHeader->setSelectedStepIndex($selectedStepIndex);
        
        $wizardHeaderRenderer = new WizardHeaderRenderer($wizardHeader);
        
        $html[] = $wizardHeaderRenderer->render();
        
        return implode(PHP_EOL, $html);
    }

    /**
     * Returns the title for the first step wizard
     * 
     * @return string
     */
    protected function getWizardFirstStepTitle()
    {
        $action = $this->getRequest()->get(\Chamilo\Core\Repository\Viewer\Manager::PARAM_ACTION);
        switch ($action)
        {
            case \Chamilo\Core\Repository\Viewer\Manager::ACTION_CREATOR :
                return $this->getTranslation('FirstStepCreate');
            case \Chamilo\Core\Repository\Viewer\Manager::ACTION_BROWSER :
                return $this->getTranslation('FirstStepBrowseInWorkspaces');
            case \Chamilo\Core\Repository\Viewer\Manager::ACTION_IMPORTER :
                return $this->getTranslation('FirstStepImport');
            default :
                return $this->getTranslation('FirstStepBrowseInWorkspaces');
        }
    }

    /**
     * Helper functionality
     * 
     * @param string $variable
     * @param array $parameters
     *
     * @return string
     */
    protected function getTranslation($variable, $parameters = array())
    {
        return Translation::getInstance()->get($variable, $parameters, Manager::context());
    }
}
