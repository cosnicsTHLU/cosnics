<?php
namespace Chamilo\Libraries\Translation;

use Chamilo\Libraries\File\PackagesContentFinder\PackagesFilesFinder;

/**
 * Implementation of the translation resources finder which scans chamilo packages for translation resources
 *
 * @package common\libraries
 */
class PackagesTranslationResourcesFinder implements TranslationResourcesFinderInterface
{

    /**
     * The packages files finder
     *
     * @var PackagesFilesFinder
     */
    private $packagesFilesFinder;

    /**
     * Constructor
     *
     * @param PackagesFilesFinder $packagesFilesFinder
     */
    public function __construct(PackagesFilesFinder $packagesFilesFinder)
    {
        $this->packagesFilesFinder = $packagesFilesFinder;
    }

    /**
     * Locates the translation resources and returns them per locale, per resource type and per domain
     *
     * @example $resource['nl_NL']['ini']['domain'] = '/path/to/resource'
     * @return string[]
     */
    public function findTranslationResources()
    {
        $resources = array();

        $translationFiles = $this->packagesFilesFinder->findFiles('Resources/i18n/', '/.*(\.i18n|\.xliff)$/');
        foreach ($translationFiles as $package => $translationFilesPerPackage)
        {
            foreach ($translationFilesPerPackage as $translationFile)
            {
                $fileParts = explode('.', basename($translationFile));
                $locale = $fileParts[0] . '_' . strtoupper($fileParts[0]);

                switch ($fileParts[1])
                {
                    case 'i18n' :
                        $type = 'ini';
                        break;
                    case 'xliff' :
                        $type = 'xliff';
                        break;
                    default :
                        $type = 'unknown';
                }

                $resources[$locale][$type][$package] = $translationFile;
            }
        }

        return $resources;
    }
}