<?php
namespace Chamilo\Core\User\Roles\DependencyInjection;

use Chamilo\Libraries\File\Path;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Extension on the dependency injection container.
 * Loads local services and parameters for this package.
 * 
 * @see http://symfony.com/doc/current/components/dependency_injection/compilation.html
 *
 * @package Chamilo\Libraries\DependencyInjection
 * @author Sven Vanpoucke - Hogeschool Gent
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class DependencyInjectionExtension extends Extension implements ExtensionInterface
{

    /**
     * Loads a specific configuration.
     * 
     * @param string[] $configuration
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container A ContainerBuilder instance
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configuration, ContainerBuilder $container)
    {
        $xmlFileLoader = new XmlFileLoader(
            $container, 
            new FileLocator(Path::getInstance()->getConfigurationPath('Chamilo\Core\User\Roles') . 'DependencyInjection'));
        
        $xmlFileLoader->load('repository.xml');
        $xmlFileLoader->load('services.xml');
    }

    /**
     * Returns the recommended alias to use in XML.
     * This alias is also the mandatory prefix to use when using YAML.
     * 
     * @return string
     */
    public function getAlias()
    {
        return 'chamilo.core.user.roles';
    }
}