<?php
namespace Chamilo\Core\Menu\Renderer\Item\Bar\Item;

use Chamilo\Core\Menu\Renderer\Item\Bar\Bar;
use Chamilo\Libraries\Platform\Translation;

/**
 *
 * @package Chamilo\Core\Menu\Renderer\Item\Bar\Item
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class LinkApplicationItem extends Bar
{

    public function getContent()
    {
        $html = array();

        if ($this->get_item()->get_parent() == 0)
        {
            $selected = $this->get_item()->is_selected();
        }

        $html[] = '<a' . ($selected ? ' class="current"' : '') . ' href="' . $this->get_item()->get_url() . '" target="' .
             $this->get_item()->get_target_string() . '">';

        $html[] = '<div class="label">' .
             $this->get_item()->get_titles()->get_translation(Translation :: getInstance()->getLanguageIsocode()) .
             '</div>';

        $html[] = '</a>';

        return implode(PHP_EOL, $html);
    }
}
