<?php
namespace Chamilo\Core\Group\Integration\Chamilo\Core\Admin;

use Chamilo\Core\Admin\Actions;
use Chamilo\Core\Admin\ActionsSupportInterface;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Tabs\DynamicAction;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

class Manager implements ActionsSupportInterface
{

    public static function get_actions()
    {
        $links = array();
        $links[] = new DynamicAction(
            Translation :: get('List', null, Utilities :: COMMON_LIBRARIES), 
            Translation :: get('ListDescription'), 
            Theme :: getInstance()->getImagesPath() . 'admin/list.png', 
            Redirect :: get_link(
                array(
                    Application :: PARAM_CONTEXT => \Chamilo\Core\Group\Manager :: context(), 
                    Application :: PARAM_ACTION => \Chamilo\Core\Group\Manager :: ACTION_BROWSE_GROUPS), 
                array(), 
                false, 
                Redirect :: TYPE_CORE));
        $links[] = new DynamicAction(
            Translation :: get('Create', null, Utilities :: COMMON_LIBRARIES), 
            Translation :: get('CreateDescription'), 
            Theme :: getInstance()->getImagesPath() . 'admin/add.png', 
            Redirect :: get_link(
                array(
                    Application :: PARAM_CONTEXT => \Chamilo\Core\Group\Manager :: context(), 
                    Application :: PARAM_ACTION => \Chamilo\Core\Group\Manager :: ACTION_CREATE_GROUP, 
                    \Chamilo\Core\Group\Manager :: PARAM_GROUP_ID => 0), 
                array(), 
                false, 
                Redirect :: TYPE_CORE));
        $links[] = new DynamicAction(
            Translation :: get('Export', null, Utilities :: COMMON_LIBRARIES), 
            Translation :: get('ExportDescription'), 
            Theme :: getInstance()->getImagesPath() . 'admin/export.png', 
            Redirect :: get_link(
                array(
                    Application :: PARAM_CONTEXT => \Chamilo\Core\Group\Manager :: context(), 
                    Application :: PARAM_ACTION => \Chamilo\Core\Group\Manager :: ACTION_EXPORT), 
                array(), 
                false, 
                Redirect :: TYPE_CORE));
        $links[] = new DynamicAction(
            Translation :: get('Import', null, Utilities :: COMMON_LIBRARIES), 
            Translation :: get('ImportDescription'), 
            Theme :: getInstance()->getImagesPath() . 'admin/import.png', 
            Redirect :: get_link(
                array(
                    Application :: PARAM_CONTEXT => \Chamilo\Core\Group\Manager :: context(), 
                    Application :: PARAM_ACTION => \Chamilo\Core\Group\Manager :: ACTION_IMPORT), 
                array(), 
                false, 
                Redirect :: TYPE_CORE));
        $links[] = new DynamicAction(
            Translation :: get('ImportGroupUsers'), 
            Translation :: get('ImportGroupUsersDescription'), 
            Theme :: getInstance()->getImagesPath() . 'admin/import.png', 
            Redirect :: get_link(
                array(
                    Application :: PARAM_CONTEXT => \Chamilo\Core\Group\Manager :: context(), 
                    Application :: PARAM_ACTION => \Chamilo\Core\Group\Manager :: ACTION_IMPORT_GROUP_USERS), 
                array(), 
                false, 
                Redirect :: TYPE_CORE));
        
        $info = new Actions(\Chamilo\Core\Group\Manager :: context());
        $info->set_links($links);
        $info->set_search(
            Redirect :: get_link(
                array(
                    Application :: PARAM_CONTEXT => \Chamilo\Core\Group\Manager :: context(), 
                    Application :: PARAM_ACTION => \Chamilo\Core\Group\Manager :: ACTION_BROWSE_GROUPS), 
                array(), 
                false, 
                Redirect :: TYPE_CORE));
        
        return $info;
    }
}
