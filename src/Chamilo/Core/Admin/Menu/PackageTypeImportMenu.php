<?php
namespace Chamilo\Core\Admin\Menu;

use Chamilo\Configuration\Package\PackageList;
use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Format\Menu\Library\HtmlMenu;
use Chamilo\Libraries\Format\Menu\Library\Renderer\HtmlMenuArrayRenderer;
use Chamilo\Libraries\Format\Menu\OptionsMenuRenderer;
use Chamilo\Libraries\Format\Menu\TreeMenuRenderer;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;

class PackageTypeImportMenu extends HtmlMenu
{

    private $format;

    private $array_renderer;

    public function __construct($current_type, $format)
    {
        $this->format = $format;
        
        parent::__construct(
            array(
                $this->get_items(
                    \Chamilo\Configuration\Package\PlatformPackageBundles::getInstance()->get_package_list())));
        
        $this->array_renderer = new HtmlMenuArrayRenderer();
        $this->forceCurrentUrl($this->get_url($current_type));
    }

    private function get_items(PackageList $package_list)
    {
        $item = array();
        $item['class'] = 'category';
        
        if (! is_null($package_list->get_type_icon()))
        {
            $item['style'] = 'background-image: url(' . $package_list->get_type_icon() . ')';
        }
        
        $item['title'] = $package_list->get_type_name();
        $item['url'] = $this->get_url($package_list->get_type());
        $item[OptionsMenuRenderer::KEY_ID] = $package_list->get_type();
        
        $sub_items = array();
        
        foreach ($package_list->get_children() as $child)
        {
            $children = $this->get_items($child);
            if ($children)
            {
                $sub_items[] = $children;
            }
        }
        
        if (count($sub_items) > 0)
        {
            $item['sub'] = $sub_items;
        }
        
        $has_links = false;
        $packages = $package_list->get_packages();
        
        foreach ($packages as $package)
        {
            $registration = \Chamilo\Configuration\Configuration::registration($package->get_context());
            
            if (! empty($registration) && $registration[Registration::PROPERTY_STATUS])
            {
                $manager_class = $package->get_context() . '\Integration\Chamilo\Core\Admin\Manager';
                
                if (class_exists($manager_class) &&
                     is_subclass_of($manager_class, 'Chamilo\Core\Admin\ImportActionsInterface', true))
                {
                    $has_links = true;
                    
                    break;
                }
            }
        }
        
        if ($has_links || (count($sub_items) > 0))
        {
            if (! $has_links)
            {
                unset($item['url']);
            }
            return $item;
        }
        
        return false;
    }

    private function get_url($type)
    {
        return (str_replace('__type__', $type, $this->format));
    }

    public function get_breadcrumbs()
    {
        $trail = BreadcrumbTrail::getInstance();
        // $this->render($this->array_renderer, 'urhere');
        // $breadcrumbs = $this->array_renderer->toArray();
        // foreach ($breadcrumbs as $crumb)
        // {
        // $str = Translation :: get('MyRepository');
        // if (substr($crumb['title'], 0, strlen($str)) == $str)
        // continue;
        // $trail->add(new Breadcrumb($crumb['url'], substr($crumb['title'], 0, strpos($crumb['title'], '('))));
        // }
        return $trail;
    }

    public function render_as_tree()
    {
        $renderer = new TreeMenuRenderer(ClassnameUtilities::getInstance()->getClassNameFromNamespace(__CLASS__, true));
        $this->render($renderer, 'sitemap');
        return $renderer->toHTML();
    }
}
