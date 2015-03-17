<?php
namespace Chamilo\Core\Repository\ContentObject\Survey\Builder\Component;

use Chamilo\Core\Repository\ContentObject\Survey\Builder\Manager;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;

class CreatorComponent extends Manager implements DelegateComponent
{

    public function run()
    {
        $factory = new ApplicationFactory(
            $this->getRequest(),
            \Chamilo\Core\Repository\Builder\Action\Manager :: context(),
            $this->get_user(),
            $this);
        return $factory->run();
    }

    public function get_additional_parameters()
    {
        return array(self :: PARAM_TYPE);
    }
}
