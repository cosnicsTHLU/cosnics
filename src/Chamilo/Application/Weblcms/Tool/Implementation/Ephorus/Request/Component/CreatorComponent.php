<?php
/**
 * User: Pieterjan Broekaert
 * Date: 30/07/12
 * Time: 12:41
 */
namespace Chamilo\Application\Weblcms\Tool\Implementation\Ephorus\Request\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\Ephorus\Request\Manager;
use Chamilo\Application\Weblcms\Tool\Implementation\Ephorus\Request\Processor\RequestProcessor;

class CreatorComponent extends Manager
{

    /**
     * Runs the component
     */
    public function run()
    {
        $base_requests = $this->get_parent()->get_base_requests();
        
        $request_processor = new RequestProcessor();
        $failures = $request_processor->hand_in_documents($base_requests);
        
        if ($failures > 0)
        {
            $is_error_message = true;
        }
        else
        {
            $is_error_message = false;
        }
        
        $message = $this->get_result(
            $failures, 
            count($base_requests), 
            'SelectedRequestNotCreated', 
            'SelectedRequestsNotCreated', 
            'SelectedRequestCreated', 
            'SelectedRequestsCreated');
        
        $this->get_parent()->redirect_after_create($message, $is_error_message);
    }
}
