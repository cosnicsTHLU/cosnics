<?php
namespace Chamilo\Libraries\DependencyInjection;

use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\DependencyInjection\CompilerPass\CacheServicesConstructorCompilerPass;
use Chamilo\Libraries\DependencyInjection\CompilerPass\ConsoleCompilerPass;
use Chamilo\Libraries\DependencyInjection\Interfaces\ICompilerPassExtension;
use Chamilo\Libraries\File\PathBuilder;
use Chamilo\Libraries\Utilities\StringUtilities;
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
class DependencyInjectionExtension extends Extension implements ExtensionInterface, ICompilerPassExtension
{

    /**
     * Loads a specific configuration.
     * 
     * @param string[] $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container A ContainerBuilder instance
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configuration, ContainerBuilder $container)
    {
        $pathBuilder = new PathBuilder(new ClassnameUtilities(new StringUtilities()));
        
        $xmlFileLoader = new XmlFileLoader(
            $container, 
            new FileLocator($pathBuilder->getConfigurationPath('Chamilo\Libraries') . 'DependencyInjection'));
        
        $xmlFileLoader->load('architecture.xml');
        $xmlFileLoader->load('cache.xml');
        $xmlFileLoader->load('file.xml');
        $xmlFileLoader->load('format.xml');
        $xmlFileLoader->load('hashing.xml');
        $xmlFileLoader->load('platform.xml');
        $xmlFileLoader->load('storage.xml');
        $xmlFileLoader->load('storage.doctrine.xml');
        $xmlFileLoader->load('storage.doctrine_test.xml');
        $xmlFileLoader->load('translation.xml');
        $xmlFileLoader->load('utilities.xml');
        $xmlFileLoader->load('vendor.xml');
        
        // Console configuration
        $xmlFileLoader->load('console.xml');
    }

    /**
     * Registers the compiler passes in the container
     * 
     * @param ContainerBuilder $container
     */
    public function registerCompilerPasses(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConsoleCompilerPass());
        $container->addCompilerPass(new CacheServicesConstructorCompilerPass());
    }

    /**
     * Returns the recommended alias to use in XML.
     * This alias is also the mandatory prefix to use when using YAML.
     * 
     * @return string
     */
    public function getAlias()
    {
        return 'chamilo.libraries';
    }
}