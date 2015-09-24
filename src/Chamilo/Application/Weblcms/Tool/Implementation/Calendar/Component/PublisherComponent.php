<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Calendar\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\Calendar\Manager;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;

class PublisherComponent extends Manager implements DelegateComponent
{

    public function get_additional_parameters()
    {
        return array(
            \Chamilo\Core\Repository\Viewer\Manager :: PARAM_ID,
            \Chamilo\Core\Repository\Viewer\Manager :: PARAM_ACTION);
    }
}
