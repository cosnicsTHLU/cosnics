<?php
namespace Chamilo\Core\Lynx\Component;

use Chamilo\Core\Lynx\Manager;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;

class SourceComponent extends Manager implements DelegateComponent
{

    public function run()
    {
        $factory = new ApplicationFactory(
            \Chamilo\Core\Lynx\Source\Manager::context(), 
            new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this));
        return $factory->run();
    }
}
