<?php
namespace Chamilo\Core\User\Storage\Repository;

use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Core\User\Storage\DataClass\UserSetting;
use Chamilo\Core\User\Storage\DataManager;
use Chamilo\Core\User\Storage\Repository\Interfaces\UserRepositoryInterface;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\ComparisonCondition;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * The repository wrapper for the user data manager
 * 
 * @package user
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class UserRepository implements UserRepositoryInterface
{

    /**
     * Finds a user by a given id
     * 
     * @param int $id
     *
     * @return \Chamilo\Core\User\Storage\DataClass\User
     */
    public function findUserById($id)
    {
        return DataManager::retrieve_by_id(User::class_name(), $id);
    }

    /**
     * Finds a user by a list of parameters
     * 
     * @param Condition $condition
     * @param int $count
     * @param int $offset
     * @param OrderBy[] $order_by
     *
     * @return User[]
     */
    public function findUsers(Condition $condition, $count = null, $offset = null, $order_by = array())
    {
        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by);
        return DataManager::retrieves(User::class_name(), $parameters)->as_array();
    }

    /**
     * Finds a user by a given email
     * 
     * @param string $email
     *
     * @return User;
     */
    public function findUserByEmail($email)
    {
        $condition = new ComparisonCondition(
            new PropertyConditionVariable(User::class_name(), User::PROPERTY_EMAIL), 
            ComparisonCondition::EQUAL, 
            new StaticConditionVariable($email));
        
        $users = $this->findUsers($condition);
        return $users[0];
    }

    /**
     * Finds a user by a given username
     * 
     * @param string $username
     *
     * @return \Chamilo\Core\User\Storage\DataClass\User
     */
    public function findUserByUsername($username)
    {
        return \Chamilo\Core\User\Storage\DataManager::retrieve_user_by_username($username);
    }

    /**
     *
     * @return User[]
     */
    public function findActiveStudents()
    {
        return $this->findActiveUsersByStatus(User::STATUS_STUDENT);
    }

    /**
     *
     * @return User[]
     */
    public function findActiveTeachers()
    {
        return $this->findActiveUsersByStatus(User::STATUS_TEACHER);
    }

    /**
     *
     * @param $status
     * @return User[]
     */
    protected function findActiveUsersByStatus($status)
    {
        $conditions = array();
        $conditions[] = new ComparisonCondition(
            new PropertyConditionVariable(User::class_name(), User::PROPERTY_STATUS), 
            ComparisonCondition::EQUAL, 
            new StaticConditionVariable($status));
        $conditions[] = new ComparisonCondition(
            new PropertyConditionVariable(User::class_name(), User::PROPERTY_ACTIVE), 
            ComparisonCondition::EQUAL, 
            new StaticConditionVariable(1));
        
        $parameters = new DataClassRetrievesParameters(new AndCondition($conditions));
        
        /**
         *
         * @var User[] $users
         */
        $users = DataManager::retrieves(User::class_name(), $parameters)->as_array();
        
        return $users;
    }

    public function create(DataClass $dataClass)
    {
        return $dataClass->create();
    }

    public function update(DataClass $dataClass)
    {
        return $dataClass->update();
    }

    public function delete(DataClass $dataClass)
    {
        return $dataClass->delete();
    }

    public function getUserSettingForSettingAndUser($context, $variable, User $user)
    {
        $setting = \Chamilo\Configuration\Storage\DataManager::retrieve_setting_from_variable_name(
            $variable, $context
        );

        $conditions = array();

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(UserSetting::class_name(), UserSetting::PROPERTY_USER_ID),
            new StaticConditionVariable($user->getId())
        );

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(UserSetting::class_name(), UserSetting::PROPERTY_SETTING_ID),
            new StaticConditionVariable($setting->getId())
        );

        $condition = new AndCondition($conditions);

        return \Chamilo\Core\User\Storage\DataManager::retrieve(
            UserSetting::class_name(), new DataClassRetrieveParameters($condition)
        );
    }

    public function createUserSettingForSettingAndUser($context, $variable, User $user, $value = null)
    {
        $userSetting = $this->getUserSettingForSettingAndUser($context, $variable, $user);
        if(!$userSetting instanceof UserSetting)
        {
            $setting = \Chamilo\Configuration\Storage\DataManager::retrieve_setting_from_variable_name(
                $variable, $context
            );

            $userSetting = new UserSetting();
            $userSetting->set_setting_id($setting->getId());
            $userSetting->set_user_id($user->getId());
            $userSetting->set_value($value);

            return $this->create($userSetting);
        }
        else
        {
            $userSetting->set_value($value);
            return $this->update($userSetting);
        }
    }
}