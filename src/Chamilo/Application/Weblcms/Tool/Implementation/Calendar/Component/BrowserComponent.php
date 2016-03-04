<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Calendar\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\Calendar\Manager;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Calendar\Renderer\Legend;
use Chamilo\Application\Weblcms\Service\CalendarRendererProvider;
use Chamilo\Libraries\Calendar\Renderer\Type\ViewRendererFactory;
use Chamilo\Libraries\Calendar\Renderer\Type\ViewRenderer;
use Chamilo\Libraries\Platform\Configuration\LocalSetting;

class BrowserComponent extends Manager
{
    const PARAM_FILTER = 'filter';
    const FILTER_TODAY = 'today';
    const FILTER_THIS_WEEK = 'week';
    const FILTER_THIS_MONTH = 'month';

    /**
     *
     * @var \Chamilo\Application\Weblcms\Tool\Action\Component\BrowserComponent
     */
    private $defaultComponent;

    /**
     *
     * @var \Chamilo\Application\Weblcms\Service\CalendarRendererProvider
     */
    private $calendarDataProvider;

    /**
     *
     * @var integer
     */
    private $currentTime;

    public function get_additional_parameters()
    {
        return array(self :: PARAM_BROWSE_PUBLICATION_TYPE);
    }

    /**
     *
     * @param BreadcrumbTrail $breadcrumbtrail
     */
    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
    }

    public function run()
    {
        $defaultComponent = $this->getDefaultComponent();
        $defaultComponent->checkAuthorization();

        $html = array();

        $html[] = $this->render_header();
        $html[] = $defaultComponent->renderToolHeader();

        $html[] = '<div class="row">';
        $html[] = $this->renderCalendar();
        $html[] = '</div>';

        $html[] = $this->render_footer();

        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @return \Chamilo\Application\Weblcms\Tool\Action\Component\BrowserComponent
     */
    public function getDefaultComponent()
    {
        if (! isset($this->defaultComponent))
        {
            $factory = new ApplicationFactory(
                \Chamilo\Application\Weblcms\Tool\Action\Manager :: context(),
                new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this));
            $this->defaultComponent = $factory->getComponent();
        }

        return $this->defaultComponent;
    }

    public function renderCalendar()
    {
        $dataProvider = $this->getCalendarDataProvider();
        $calendarLegend = new Legend($dataProvider);

        $rendererFactory = new ViewRendererFactory(
            $this->getCurrentRendererType(),
            $dataProvider,
            $calendarLegend,
            $this->getCurrentRendererTime());
        $renderer = $rendererFactory->getRenderer();

        if ($this->getCurrentRendererType() == ViewRenderer :: TYPE_DAY ||
             $this->getCurrentRendererType() == ViewRenderer :: TYPE_WEEK)
        {
            $renderer->setStartHour(
                LocalSetting :: getInstance()->get('working_hours_start', 'Chamilo\Libraries\Calendar'));
            $renderer->setEndHour(LocalSetting :: getInstance()->get('working_hours_end', 'Chamilo\Libraries\Calendar'));
            $renderer->setHideOtherHours(
                LocalSetting :: getInstance()->get('hide_non_working_hours', 'Chamilo\Libraries\Calendar'));
        }

        return $renderer->render();
    }

    /**
     *
     * @return string
     */
    public function getCurrentRendererType()
    {
        $requestRendererType = $this->getRequest()->query->get(ViewRenderer :: PARAM_TYPE);

        if (! $requestRendererType)
        {
            return LocalSetting :: getInstance()->get('default_view', 'Chamilo\Libraries\Calendar');
        }

        return $requestRendererType;
    }

    /**
     *
     * @return integer
     */
    public function getCurrentRendererTime()
    {
        if (! isset($this->currentTime))
        {
            $this->currentTime = $this->getRequest()->query->get(ViewRenderer :: PARAM_TIME, time());
        }

        return $this->currentTime;
    }

    public function getCalendarDataProvider()
    {
        if (! isset($this->calendarDataProvider))
        {
            $displayParameters = $this->getDefaultComponent()->get_parameters();

            $this->calendarDataProvider = new CalendarRendererProvider(
                $this->getDefaultComponent(),
                $this->get_user(),
                $this->get_user(),
                $displayParameters);
        }

        return $this->calendarDataProvider;
    }
}
