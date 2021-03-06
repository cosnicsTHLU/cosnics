<?php
namespace Chamilo\Core\Repository\Instance\Storage\DataClass;

class PersonalInstance extends Instance
{
    const PROPERTY_USER_ID = 'user_id';

    public function set_user_id($user_id)
    {
        if (isset($user_id) && strlen($user_id) > 0)
        {
            $this->set_additional_property(self::PROPERTY_USER_ID, $user_id);
        }
    }

    public function get_user_id()
    {
        return $this->get_additional_property(self::PROPERTY_USER_ID);
    }

    public static function get_additional_property_names($extended_property_names = array())
    {
        return array(self::PROPERTY_USER_ID);
    }
}
