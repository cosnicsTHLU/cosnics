<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Link\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\Link\Manager;

class ReportingViewerComponent extends Manager
{

    public function get_additional_parameters()
    {
        return array(
            \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID, 
            \Chamilo\Application\Weblcms\Tool\Manager::PARAM_COMPLEX_ID, 
            \Chamilo\Application\Weblcms\Tool\Manager::PARAM_TEMPLATE_NAME, 
            \Chamilo\Application\Weblcms\Manager::PARAM_COURSE);
    }
}
