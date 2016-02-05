<?php
namespace Chamilo\Core\Repository\Integration\Chamilo\Core\Menu\Renderer\Item\BootstrapBar\Item;

use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Core\Menu\Renderer\Item\BootstrapBar\BootstrapBar;

/**
 *
 * @package Chamilo\Core\User\Integration\Chamilo\Core\Menu\Renderer\Item\Bar\Item
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class WorkspaceItem extends BootstrapBar
{

    public function isItemSelected()
    {
        $currentContext = $this->getMenuRenderer()->getRequest()->get(Application :: PARAM_CONTEXT);
        $currentWorkspace = $this->getMenuRenderer()->getRequest()->get(
            \Chamilo\Core\Repository\Manager :: PARAM_WORKSPACE_ID);
        return ($currentContext == \Chamilo\Core\Repository\Manager :: package() &&
             $currentWorkspace == $this->getItem()->getWorkspaceId());
    }

    public function getContent()
    {
        $selected = $this->isSelected();

        if ($selected)
        {
            $class = 'class="current" ';
        }
        else
        {
            $class = '';
        }

        $redirect = new Redirect(
            array(
                Application :: PARAM_CONTEXT => \Chamilo\Core\Repository\Manager :: package(),
                \Chamilo\Core\Repository\Manager :: PARAM_WORKSPACE_ID => $this->getItem()->getWorkspaceId()));

        $html[] = '<a ' . $class . 'href="' . $redirect->getUrl() . '">';
        $title = $this->getItem()->getName();

        if ($this->getItem()->show_icon())
        {
            $imagePath = Theme :: getInstance()->getImagePath(\Chamilo\Core\Repository\Manager :: package(), 'Logo/16');

            $html[] = '<img class="chamilo-menu-item-icon" src="' . $imagePath . '" title="' . $title . '" alt="' .
                 $title . '" />';
        }

        if ($this->getItem()->show_title())
        {
            $html[] = '<div class="chamilo-menu-item-label' .
                 ($this->getItem()->show_icon() ? ' chamilo-menu-item-label-with-image' : '') . '">' . $title . '</div>';
        }

        $html[] = '</a>';

        return implode(PHP_EOL, $html);
    }
}