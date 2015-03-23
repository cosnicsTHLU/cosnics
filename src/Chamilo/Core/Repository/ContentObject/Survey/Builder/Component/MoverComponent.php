<?php
namespace Chamilo\Core\Repository\ContentObject\Survey\Builder\Component;

use Chamilo\Core\Repository\ContentObject\Survey\Builder\Manager;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;

class MoverComponent extends Manager
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
}
