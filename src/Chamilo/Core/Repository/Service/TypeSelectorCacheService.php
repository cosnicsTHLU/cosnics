<?php

namespace Chamilo\Core\Repository\Service;

use Chamilo\Core\Repository\Selector\TypeSelectorFactory;
use Chamilo\Libraries\Cache\Doctrine\Service\DoctrineFilesystemCacheService;
use Chamilo\Libraries\Cache\Interfaces\UserBasedCacheInterface;
use Chamilo\Libraries\Cache\ParameterBag;

/**
 *
 * @package Chamilo\Core\Repository\Service
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class TypeSelectorCacheService extends DoctrineFilesystemCacheService implements UserBasedCacheInterface
{
    // Parameters
    const PARAM_TYPES = 'types';
    const PARAM_USER_IDENTIFIER = 'user_identifier';
    const PARAM_MODE = 'mode';
    const PARAM_DEFAULT_SORTING = 'default_sorting';

    /**
     *
     * @var \Chamilo\Core\Repository\Selector\TypeSelectorFactory
     */
    private $typeSelectorFactory;

    /**
     *
     * @param \Chamilo\Core\Repository\Selector\TypeSelectorFactory $typeSelectorFactory
     */
    public function __construct(TypeSelectorFactory $typeSelectorFactory)
    {
        $this->typeSelectorFactory = $typeSelectorFactory;
    }

    /**
     *
     * @return \Chamilo\Core\Repository\Selector\TypeSelectorFactory
     */
    public function getTypeSelectorFactory()
    {
        return $this->typeSelectorFactory;
    }

    /**
     *
     * @param \Chamilo\Core\Repository\Selector\TypeSelectorFactory $typeSelectorFactory
     */
    public function setTypeSelectorFactory($typeSelectorFactory)
    {
        $this->typeSelectorFactory = $typeSelectorFactory;
    }

    /**
     *
     * @see \Chamilo\Libraries\Cache\IdentifiableCacheService::warmUpForIdentifier()
     */
    public function warmUpForIdentifier($identifier)
    {
        $typeSelector = $this->getTypeSelectorFactory()->buildTypeSelector();

        return $this->getCacheProvider()->save((string) $identifier, $typeSelector);
    }

    /**
     *
     * @see \Chamilo\Libraries\Cache\Doctrine\DoctrineCacheService::getCachePathNamespace()
     */
    public function getCachePathNamespace()
    {
        return 'Chamilo\Core\Repository\Selector';
    }

    /**
     *
     * @see \Chamilo\Libraries\Cache\IdentifiableCacheService::getIdentifiers()
     */
    public function getIdentifiers()
    {
        return array();
    }

    public function getForContentObjectTypesUserIdentifierAndMode(
        $contentObjectTypes, $userIdentifier = null, $mode = TypeSelectorFactory::MODE_CATEGORIES,
        $defaultSorting = true
    )
    {
        $parameterBag = new ParameterBag(
            array(
                self::PARAM_TYPES => $contentObjectTypes, self::PARAM_USER_IDENTIFIER => $userIdentifier,
                self::PARAM_MODE => $mode, self::PARAM_DEFAULT_SORTING => $defaultSorting
            )
        );

        return $this->getForIdentifier($parameterBag);
    }
}