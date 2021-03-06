<?php
namespace Chamilo\Core\Repository\ContentObject\Link\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\Includeable;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;

/**
 * $Id: link.class.php 200 2009-11-13 12:30:04Z kariboe $
 * 
 * @package repository.lib.content_object.link
 */
class Link extends ContentObject implements Versionable, Includeable
{
    const PROPERTY_URL = 'url';
    const PROPERTY_SHOW_IN_IFRAME = 'show_in_iframe';

    public static function get_type_name()
    {
        return ClassnameUtilities::getInstance()->getClassNameFromNamespace(self::class_name(), true);
    }

    public function get_url()
    {
        return $this->get_additional_property(self::PROPERTY_URL);
    }

    public function set_url($url)
    {
        $url = self::complete_url($url);
        return $this->set_additional_property(self::PROPERTY_URL, $url);
    }

    public function get_show_in_iframe()
    {
        return $this->get_additional_property(self::PROPERTY_SHOW_IN_IFRAME);
    }

    public function set_show_in_iframe($status)
    {
        return $this->set_additional_property(self::PROPERTY_SHOW_IN_IFRAME, $status);
    }

    public static function get_additional_property_names()
    {
        return array(self::PROPERTY_URL, self::PROPERTY_SHOW_IN_IFRAME);
    }

    /**
     * Validates the url, URL beginning with / are internal URL's and considered complete, URLS that contain :// are
     * considered complete as well.
     * In any other case the URL is appended with 'http://' at the beginning.
     * 
     * @param String $url
     * @return String completed url
     */
    public static function complete_url($url)
    {
        if (substr($url, 0, 1) == '/' || strstr($url, '://'))
        {
            return $url;
        }
        else
        {
            return 'http://' . $url;
        }
    }

    public static function get_searchable_property_names()
    {
        return array(self::PROPERTY_URL);
    }
}
