<?php
namespace Chamilo\Core\Repository\ContentObject\RssFeed\Integration\Chamilo\Core\Home\Form;

use Chamilo\Core\Home\Form\ConfigurationForm;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Core\Repository\ContentObject\RssFeed\Integration\Chamilo\Core\Home\Connector;
use Chamilo\Core\Repository\ContentObject\RssFeed\Integration\Chamilo\Core\Home\Type\Feeder;

class FeederForm extends ConfigurationForm
{

    /**
     *
     * @see \Chamilo\Core\Home\Form\ConfigurationForm::addSettings()
     */
    public function addSettings()
    {
        $connector = new Connector();

        $this->addElement(
            'select',
            Feeder :: CONFIGURATION_OBJECT_ID,
            Translation :: get('UseObject'),
            $connector->get_rss_feed_objects());
    }

    public function setDefaults()
    {
        $defaults = array();

        $defaults[Feeder :: CONFIGURATION_OBJECT_ID] = $this->getBlock()->getSetting(
            Feeder :: CONFIGURATION_OBJECT_ID,
            0);

        parent :: setDefaults($defaults);
    }
}