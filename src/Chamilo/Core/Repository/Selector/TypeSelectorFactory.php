<?php

namespace Chamilo\Core\Repository\Selector;

use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Core\Repository\Selector\Option\ContentObjectTypeSelectorOption;
use Chamilo\Core\Repository\Selector\TypeSelector;
use Chamilo\Core\Repository\Selector\TypeSelectorCategory;
use Chamilo\Core\Repository\Service\TypeSelectorCacheService;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 *
 * @package Chamilo\Core\Repository\Selector
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class TypeSelectorFactory
{
    const MODE_CATEGORIES = 1;
    const MODE_FLAT_LIST = 2;

    /**
     *
     * @var string[]
     */
    private $contentObjectTypes;

    /**
     *
     * @var integer
     */
    private $userIdentifier;

    /**
     * @var int
     */
    protected $mode;

    /**
     * @var bool
     */
    protected $defaultSorting;

    /**
     *
     * @param string[] $contentObjectTypes
     * @param integer $userIdentifier
     * @param int $mode
     * @param bool $defaultSorting
     */
    public function __construct($contentObjectTypes = null, $userIdentifier = null, $mode = self::MODE_CATEGORIES, $defaultSorting = true)
    {
        $this->contentObjectTypes = $contentObjectTypes;
        $this->userIdentifier = $userIdentifier;
        $this->mode = $mode;
        $this->defaultSorting = $defaultSorting;
    }

    /**
     *
     * @return string[]
     */
    public function getContentObjectTypes()
    {
        return $this->contentObjectTypes;
    }

    /**
     *
     * @param string[] $contentObjectTypes
     */
    public function setContentObjectTypes($contentObjectTypes)
    {
        $this->contentObjectTypes = $contentObjectTypes;
    }

    /**
     *
     * @return integer
     */
    public function getUserIdentifier()
    {
        return $this->userIdentifier;
    }

    /**
     *
     * @param integer $userIdentifier
     */
    public function setUserIdentifier($userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;
    }

    /**
     *
     * @return \Chamilo\Core\Repository\Selector\TypeSelector
     */
    public function getTypeSelector()
    {
        $typeSelectorCacheService = new TypeSelectorCacheService($this);

        return $typeSelectorCacheService->getForContentObjectTypesUserIdentifierAndMode(
            $this->getContentObjectTypes(),
            $this->getUserIdentifier(),
            $this->mode,
            $this->defaultSorting
        );
    }

    /**
     *
     * @return \Chamilo\Core\Repository\Selector\TypeSelector
     */
    public function buildTypeSelector()
    {
        $typeSelector = new TypeSelector();
        $helperTypes = DataManager::get_active_helper_types();

        $contexts = array();

        foreach ($this->getContentObjectTypes() as $contentObjectType)
        {
            $classnameUtilities = ClassnameUtilities::getInstance();
            $namespace = $classnameUtilities->getNamespaceFromClassname($contentObjectType);
            $contexts[] = $classnameUtilities->getNamespaceParent($namespace, 2);
        }

        $templateRegistrations = \Chamilo\Core\Repository\Configuration::registrations_by_types(
            $contexts,
            $this->getUserIdentifier()
        );

        foreach ($templateRegistrations as $templateRegistration)
        {
            $type = $templateRegistration->get_content_object_type() . '\Storage\DataClass\\' .
                ClassnameUtilities::getInstance()->getPackageNameFromNamespace(
                    $templateRegistration->get_content_object_type()
                );

            if (ContentObject::is_available($type))
            {
                if (in_array($type, $helperTypes))
                {
                    continue;
                }

                $registration = \Chamilo\Configuration\Configuration::registration(
                    $templateRegistration->get_content_object_type()
                );

                $contentObjectName = $templateRegistration->get_template()->translate('TypeName');

                $option = new ContentObjectTypeSelectorOption($contentObjectName, (int) $templateRegistration->get_id());

                if($this->mode == self::MODE_CATEGORIES)
                {
                    $categoryType = $registration[Registration::PROPERTY_CATEGORY];

                    if (!$typeSelector->category_type_exists($categoryType))
                    {
                        $typeSelectorCategory = new TypeSelectorCategory(
                            $categoryType,
                            Translation::get(
                                (string) StringUtilities::getInstance()->createString($categoryType)->upperCamelize()
                            )
                        );

                        $typeSelector->add_category($typeSelectorCategory);
                    }

                    $typeSelectorCategory = $typeSelector->get_category_by_type($categoryType);
                    $typeSelectorCategory->add_option($option);
                }
                else
                {
                    $typeSelector->add_option($option);
                }
            }
        }

        if($this->defaultSorting)
        {
            $typeSelector->sort();
        }

        return $typeSelector;
    }
}