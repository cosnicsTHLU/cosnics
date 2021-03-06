<?php
namespace Chamilo\Core\Install\Component;

use Chamilo\Core\Install\Factory;
use Chamilo\Core\Install\Manager;
use Chamilo\Core\Install\Observer\InstallerObserver;
use Chamilo\Core\Install\StepResult;
use Chamilo\Libraries\Architecture\Interfaces\NoAuthenticationSupport;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Platform\Translation;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 *
 * @package Chamilo\Core\Install\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 */
class InstallerComponent extends Manager implements NoAuthenticationSupport, InstallerObserver
{

    private $installer;

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->checkInstallationAllowed();

        $this->installer = $this->getInstaller($this);

        $wizardProcess = $this;

        session_write_close();

        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($wizardProcess)
            {
                echo $wizardProcess->render_header();
                flush();

                $wizardProcess->getInstaller($this)->run();
                flush();

                echo $wizardProcess->render_footer();
                flush();

                session_start();
                Session::unregister(self::PARAM_SETTINGS);
                Session::unregister(self::PARAM_LANGUAGE);
                session_write_close();
            });

        $response->send();
    }

    /**
     *
     * @return \Chamilo\Core\Install\PlatformInstaller
     */
    public function getInstaller(InstallerObserver $installerObserver)
    {
        if (! isset($this->installer))
        {
            $values = unserialize(Session::retrieve(self::PARAM_SETTINGS));

            $factory = new Factory();
            $this->installer = $factory->getInstallerFromArray($installerObserver, $values);
            unset($values);
        }

        return $this->installer;
    }

    /**
     *
     * @see \Chamilo\Core\Install\Manager::render_header()
     */
    public function render_header()
    {
        $html = array();

        $html[] = parent::render_header();

        $html[] = ResourceManager::getInstance()->get_resource_html(
            Path::getInstance()->getJavascriptPath('Chamilo\Core\Install', true) . 'InstallProcess.js');

        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @param string $title
     * @param string $result
     * @param string $message
     * @param string $image
     * @return string
     */
    public function renderResult($title, $result, $message, $image)
    {
        $html = array();

        $html[] = $this->renderResultHeader($title, $result, $image);
        $html[] = $message;
        $html[] = $this->renderResultFooter();

        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @param string $title
     * @param string $result
     * @param string $image
     * @return string
     */
    public function renderResultHeader($title, $result, $image)
    {
        $result_class = ($result ? 'installation-step-successful' : 'installation-step-failed');

        $html = array();
        $html[] = '<div class="installation-step installation-step-collapsed ' . $result_class .
             '" style="background-image: url(' . $image . ');">';
        $html[] = '<div class="title">' . $title . '</div>';
        $html[] = '<div class="description">';

        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @return string
     */
    public function renderResultFooter()
    {
        $html = array();

        $html[] = '</div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::beforeInstallation()
     */
    public function beforeInstallation()
    {
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::beforePreProduction()
     */
    public function beforePreProduction()
    {
        return '<h3>' . Translation::get('PreProduction') . '</h3>';
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::afterPreProductionDatabaseCreated()
     */
    public function afterPreProductionDatabaseCreated(StepResult $result)
    {
        $image = Theme::getInstance()->getImagePath('Chamilo\Core\Install', 'Place/Database');
        return $this->renderResult(
            Translation::get('Database'),
            $result->get_success(),
            implode('<br />' . "\n", $result->get_messages()),
            $image);
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::afterPreProductionConfigurationFileWritten()
     */
    public function afterPreProductionConfigurationFileWritten(StepResult $result)
    {
        $image = Theme::getInstance()->getImagePath('Chamilo\Core\Install', 'Place/Config');
        return $this->renderResult(
            Translation::get('Configuration'),
            $result->get_success(),
            implode('<br />' . "\n", $result->get_messages()),
            $image);
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::afterPreProduction()
     */
    public function afterPreProduction()
    {
        return '<div class="clear"></div>';
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::beforePackagesInstallation()
     */
    public function beforePackagesInstallation()
    {
        return '<h3>' . Translation::get('Packages') . '</h3>';
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::afterPackagesInstallation()
     */
    public function afterPackagesInstallation()
    {
        return '<div class="clear"></div>';
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::beforePackageInstallation()
     */
    public function beforePackageInstallation($context)
    {
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::afterPackageInstallation()
     */
    public function afterPackageInstallation(StepResult $result)
    {
        $image = Theme::getInstance()->getImagePath($result->get_context(), 'Logo/22');
        $title = Translation::get('TypeName', null, $result->get_context()) . ' (' . $result->get_context() . ')';

        return $this->renderResult(
            $title,
            $result->get_success(),
            implode('<br />' . "\n", $result->get_messages()),
            $image);
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::beforeFilesystemPrepared()
     */
    public function beforeFilesystemPrepared()
    {
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::afterFilesystemPrepared()
     */
    public function afterFilesystemPrepared(StepResult $result)
    {
        $image = Theme::getInstance()->getImagePath('Chamilo\Core\Install', 'Place/Folder');
        return $this->renderResult(
            Translation::get('Folders'),
            $result->get_success(),
            implode('<br />' . "\n", $result->get_messages()),
            $image);
    }

    /**
     *
     * @see \Chamilo\Core\Install\Observer\InstallerObserver::afterInstallation()
     */
    public function afterInstallation()
    {
        $message = '<a href="' . Path::getInstance()->getBasePath(true) . '">' .
             Translation::get('GoToYourNewlyCreatedPortal') . '</a>';
        $image = Theme::getInstance()->getImagePath('Chamilo\Core\Install', 'Place/Finished');
        return $this->renderResult(Translation::get('InstallationFinished'), true, $message, $image);
    }
}
