<?php
namespace Chamilo\Core\Repository\Workspace\Favourite\Component;

use Chamilo\Core\Repository\Workspace\Favourite\Manager;
use Chamilo\Core\Repository\Workspace\Favourite\Repository\FavouriteRepository;
use Chamilo\Core\Repository\Workspace\Favourite\Service\FavouriteService;
use Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

/**
 *
 * @package Chamilo\Core\Repository\Workspace\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class DeleterComponent extends Manager
{

    /**
     * Executes this controller
     */
    public function run()
    {
        $workspaceIdentifiers = $this->getRequest()->get(
            \Chamilo\Core\Repository\Workspace\Manager::PARAM_WORKSPACE_ID);
        
        try
        {
            if (empty($workspaceIdentifiers))
            {
                throw new NoObjectSelectedException(Translation::get('Workspace'));
            }
            
            if (! is_array($workspaceIdentifiers))
            {
                $workspaceIdentifiers = array($workspaceIdentifiers);
            }
            
            $favouriteService = new FavouriteService(new FavouriteRepository());
            
            foreach ($workspaceIdentifiers as $workspaceIdentifier)
            {
                $success = $favouriteService->deleteWorkspaceByUserAndWorkspaceIdentifier(
                    $this->get_user(), 
                    $workspaceIdentifier);
                
                if (! $success)
                {
                    throw new \Exception(
                        Translation::get(
                            'ObjectNotDeleted', 
                            array('OBJECT' => Translation::get('WorkspaceUserFavourite')), 
                            Utilities::COMMON_LIBRARIES));
                }
            }
            
            $success = true;
            $message = Translation::get(
                'ObjectDeleted', 
                array('OBJECT' => Translation::get('WorkspaceUserFavourite')), 
                Utilities::COMMON_LIBRARIES);
        }
        catch (\Exception $ex)
        {
            $success = false;
            $message = $ex->getMessage();
        }
        
        $action = $this->getRequest()->get(\Chamilo\Core\Repository\Workspace\Manager::PARAM_BROWSER_SOURCE);
        if (! isset($action))
        {
            $this->redirect($message, ! $success, array(self::PARAM_ACTION => $action));
        }
        else
        {
            $this->redirect(
                Translation::get(
                    'ObjectDeleted', 
                    array('OBJECT' => Translation::get('WorkspaceUserFavourite')), 
                    Utilities::COMMON_LIBRARIES), 
                false, 
                array(\Chamilo\Core\Repository\Workspace\Manager::PARAM_ACTION => $action), 
                array(self::PARAM_ACTION));
        }
    }
}