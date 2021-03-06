<?php
namespace Chamilo\Libraries\File\PackagesContentFinder;

/**
 * Base class that can be used by other classes to include the PackagesClassFinder
 * 
 * @package common\libraries
 * @author Sven Vanpoucke - Hogeschool Gent
 */
abstract class PackagesClassFinderAware
{

    /**
     * The class finder for packages
     * 
     * @var PackagesClassFinder
     */
    private $packagesClassFinder;

    /**
     * Constructor
     * 
     * @param PackagesClassFinder $packages_class_finder
     */
    public function __construct(PackagesClassFinder $packages_class_finder = null)
    {
        $this->setPackagesClassFinder($packages_class_finder);
    }

    /**
     *
     * @param PackagesClassFinder $packagesClassFinder
     *
     * @throws \InvalidArgumentException
     */
    public function setPackagesClassFinder(PackagesClassFinder $packagesClassFinder)
    {
        if (! $packagesClassFinder instanceof PackagesClassFinder)
        {
            throw new \InvalidArgumentException(
                'The given packages class finder should be an instance of' .
                     ' "\common\libraries\PackagesClassFinder", instead "' . get_class($packagesClassFinder) .
                     '" was given');
        }
        $this->packagesClassFinder = $packagesClassFinder;
    }

    /**
     *
     * @return PackagesClassFinder
     */
    public function getPackagesClassFinder()
    {
        return $this->packagesClassFinder;
    }
}