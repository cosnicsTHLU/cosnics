<?php
namespace Chamilo\Application\Weblcms\Tool\Action\Component;

use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Tool\Action\Manager;
use Chamilo\Core\Repository\ContentObject\Introduction\Storage\DataClass\Introduction;
use Chamilo\Core\Repository\Form\ContentObjectForm;
use Chamilo\Core\Repository\RepositoryRights;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;

/**
 * $Id: edit.class.php 216 2009-11-13 14:08:06Z kariboe $
 *
 * @package application.lib.weblcms.tool.component
 */
class ContentObjectUpdaterComponent extends Manager implements DelegateComponent
{

    public function run()
    {
        $pid = Request :: get(\Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID) ? Request :: get(
            \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID) : $_POST[\Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID];

        $publication = \Chamilo\Application\Weblcms\Storage\DataManager :: retrieve_by_id(
            ContentObjectPublication :: class_name(),
            $pid);

        $has_edit_right = ($publication->get_content_object()->get_owner_id() == $this->get_user_id() || RepositoryRights :: get_instance()->is_allowed_in_user_subtree(
            RepositoryRights :: COLLABORATE_RIGHT,
            $publication->get_content_object()->get_id(),
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $publication->get_content_object()->get_owner_id(),
            $this->get_user_id()));

        if ($has_edit_right)
        {
            $content_object = $publication->get_content_object();

            if ($content_object->get_type() == Introduction :: class_name())
            {
                $publication->ignore_display_order();
            }

            BreadcrumbTrail :: get_instance()->add(
                new Breadcrumb(
                    $this->get_url(),
                    Translation :: get(
                        'ToolContentObjectUpdateComponent',
                        array('TITLE' => $content_object->get_title()))));

            $form = ContentObjectForm :: factory(
                ContentObjectForm :: TYPE_EDIT,
                $content_object,
                'edit',
                'post',
                $this->get_url(array(\Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID => $pid)));

            if ($form->validate())
            {
                $form->update_content_object();
                if ($form->is_version())
                {
                    $publication->set_content_object_id($content_object->get_latest_version_id());
                    $publication->update();
                }

                $tool = $this->get_tool_id();

                if ($tool == 'learning_path')
                {
                    $params[\Chamilo\Application\Weblcms\Tool\Manager :: PARAM_ACTION] = null;
                    $params['display_action'] = 'view';
                    $params[\Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID] = Request :: get(
                        \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID);
                }

                if ($tool != 'learning_path')
                {
                    $filter = array(
                        \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_ACTION,
                        \Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID);
                }
                else
                {
                    $filter = array();
                }

                $this->redirect(Translation :: get('ContentObjectUpdated'), false, $params, $filter);
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
            $this->redirect(
                Translation :: get("NotAllowed"),
                true,
                array(\Chamilo\Application\Weblcms\Tool\Manager :: PARAM_PUBLICATION_ID => null, 'tool_action' => null));
        }
    }
}
