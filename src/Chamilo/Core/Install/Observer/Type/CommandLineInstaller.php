<?php
namespace Chamilo\Core\Install\Observer\Type;

use Chamilo\Core\Install\Configuration;
use Chamilo\Core\Install\Factory;
use Chamilo\Core\Install\Observer\InstallerObserver;
use Chamilo\Core\Install\StepResult;

class CommandLineInstaller implements InstallerObserver
{

    private $config_file;

    private $installer;

    public function __construct($config_file)
    {
        $this->config_file = $config_file;
    }

    public function run()
    {
        $installer_config = new Configuration();
        $installer_config->load_config_file($this->config_file);

        $installer_factory = new Factory();
        $this->installer = $installer_factory->build_installer($installer_config);
        $this->installer->add_observer($this);
        $this->installer->perform_install();
    }

    private function check_result(StepResult $result)
    {
        if ($result->get_success())
        {
            echo "Ok";
            ob_flush();
            return;
        }

        $reason = implode(", ", $result->get_messages());
        echo "Ko ({$reason})";
        ob_flush();
    }

    public function beforeInstallation()
    {
        echo "install started ...\n\n";
    }

    public function beforePreProduction()
    {
        echo "\tPRE-PRODUCTION\n";
    }

    public function afterPreProductionDatabaseCreated(StepResult $result)
    {
        echo "\t\t DB created ... " . $this->check_result($result) . "\n";
    }

    public function afterPreProductionConfigurationFileWritten(StepResult $result)
    {
        echo "\t\t Config File Written ... " . $this->check_result($result) . "\n";
    }

    public function afterPreProduction()
    {
        echo "\n";
        ob_flush();
    }

    public function beforeFilesystemPrepared()
    {
        echo "\tFILE SYSTEM PREPARATION\n";
    }

    public function afterFilesystemPrepared(StepResult $result)
    {
        echo "\t\t File system prepared ... " . $this->check_result($result) . "\n";
    }

    public function afterInstallation()
    {
        echo "\n\nInstallation completed !\n";
        ob_flush();
    }

    public function beforePackagesInstallation()
    {
        echo "\PACKAGES INSTALLATION\n";
    }

    public function afterPackagesInstallation()
    {
        echo "\n";
        ob_flush();
    }

    public function beforePackageInstallation($context)
    {
        echo "\t\t Installing package {$context} ... ";
    }

    public function afterPackageInstallation(StepResult $result)
    {
        echo $this->check_result($result) . "\n";
        ob_flush();
    }
}
