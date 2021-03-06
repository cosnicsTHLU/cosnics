<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Link;

use Chamilo\Application\Weblcms\Tool\Interfaces\IntroductionTextSupportInterface;
use Chamilo\Core\Repository\ContentObject\Link\Storage\DataClass\Link;
use Chamilo\Core\Repository\ContentObject\RssFeed\Storage\DataClass\RssFeed;
use Chamilo\Libraries\Architecture\Interfaces\Categorizable;

/**
 * $Id: link_tool.class.php 216 2009-11-13 14:08:06Z kariboe $
 * 
 * @package application.lib.weblcms.tool.link
 */

/**
 * This tool allows a user to publish links in his or her course.
 */
abstract class Manager extends \Chamilo\Application\Weblcms\Tool\Manager implements Categorizable, 
    IntroductionTextSupportInterface
{
    const TOOL_NAME = 'link';

    public static function get_allowed_types()
    {
        return array(Link::class_name(), RssFeed::class_name());
    }
}
