<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Template;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\Assessment\AssessmentOverviewBlock;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\Assessment\AssessmentUserScoresBlock;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Core\Reporting\ReportingTemplate;
use Chamilo\Core\Repository\ContentObject\Assessment\Storage\DataClass\Assessment;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 *
 * @package application.weblcms.php.reporting.templates Reporting template with an overview of scores of each assessment
 *          per user
 * @author Joris Willems <joris.willems@gmail.com>
 * @author Alexander Van Paemel
 */
class AssessmentScoresTemplate extends ReportingTemplate
{

    public function __construct($parent)
    {
        parent::__construct($parent);
        
        $this->init_parameters();
        
        $this->add_breadcrumbs();
        
        // Calculate number of assessments
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class_name(), 
                ContentObjectPublication::PROPERTY_COURSE_ID), 
            new StaticConditionVariable($this->course_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublication::class_name(), 
                ContentObjectPublication::PROPERTY_TOOL), 
            new StaticConditionVariable(Assessment::class_name()));
        $condition = new AndCondition($conditions);
        
        $order_by = new OrderBy(
            new PropertyConditionVariable(
                ContentObjectPublication::class_name(), 
                ContentObjectPublication::PROPERTY_MODIFIED_DATE));
        
        $publications = \Chamilo\Application\Weblcms\Storage\DataManager::retrieve_content_object_publications(
            $condition, 
            $order_by);
        
        $this->size = $publications->size();
        
        foreach ($publications->as_array() as $publication)
        {
            $this->th_titles[] = $publication->get_content_object()->get_title();
        }
        
        $this->add_reporting_block(new AssessmentUserScoresBlock($this, true));
        $this->add_reporting_block(new AssessmentOverviewBlock($this));
    }

    private function init_parameters()
    {
        $this->course_id = Request::get(\Chamilo\Application\Weblcms\Manager::PARAM_COURSE);
        if ($this->course_id)
        {
            $this->set_parameter(\Chamilo\Application\Weblcms\Manager::PARAM_COURSE, $this->course_id);
        }
        $sel = (Request::post('sel')) ? Request::post('sel') : Request::get('sel');
        if ($sel)
        {
            $this->set_parameter('sel', $sel);
        }
    }

    /**
     * Adds the breadcrumbs to the breadcrumbtrail
     */
    protected function add_breadcrumbs()
    {
        $trail = BreadcrumbTrail::getInstance();
        
        $trail->add(
            new Breadcrumb(
                $this->get_url(
                    array(\Chamilo\Core\Reporting\Viewer\Manager::PARAM_BLOCK_ID => 4), 
                    array(\Chamilo\Application\Weblcms\Manager::PARAM_TEMPLATE_ID)), 
                Translation::get('LastAccessToToolsBlock')));
        
        $trail->add(new Breadcrumb($this->get_url(), Translation::get('AssessmentScores')));
    }
}
