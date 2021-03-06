<?php
namespace Chamilo\Core\Repository\Common;

use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Demo\Annotation\Deprecated;
use DOMDocument;
use DOMXPath;

class ContentObjectResourceRenderer
{

    private $context;

    private $description;

    /**
     * Handle description as full html or not
     * 
     * @var bool
     */
    private $full_html;

    /**
     * @var DOMXPath $dom_xpath
     */
    private $dom_xpath;

    /**
     * @var DOMDocument $dom_document
     */
    private $dom_document;

    public function __construct($context, $description, $full_html = false)
    {
        $this->context = $context;
        $this->description = $description;
        $this->full_html = $full_html;
    }

    public function run()
    {
        $description = $this->description;
        
        $this->dom_document = new DOMDocument();
        $this->dom_document->loadHTML('<?xml encoding="UTF-8">' . $description);
        $this->dom_document->removeChild($this->dom_document->firstChild);
        $this->dom_xpath = new DOMXPath($this->dom_document);
        
        if (! $this->full_html)
        {
            $body_nodes = $this->dom_xpath->query('body/*');
            $fragment = $this->dom_document->createDocumentFragment();
            foreach ($body_nodes as $child)
            {
                $fragment->appendChild($child);
            }
            $this->dom_document->replaceChild($fragment, $this->dom_document->firstChild);
        }
        else
        {
            $this->dom_document->removeChild($this->dom_document->firstChild);
        }

        $this->processResources();

        $this->processContentObjectPlaceholders();
        
        return $this->dom_document->saveHTML();
    }

    /**
     * Add given nodes to the given document fragment
     * 
     * @param \DOMDocumentFragment $documentFragment
     * @param \DOMNodeList $nodes
     */
    protected function addNodesToDocumentFragment(\DOMDocumentFragment $documentFragment, \DOMNodeList $nodes)
    {
        foreach ($nodes as $node)
        {
            $documentFragment->appendChild($node);
        }
    }

    protected function processContentObjectPlaceholders()
    {
        $placeholders = $this->dom_xpath->query('//*[@data-co-id]'); //select all elements with the data-co-id attribute
        foreach($placeholders as $placeholder) {
            /**
             * @var \DOMNode $placeholder
             */
            $contentObjectId = $placeholder->getAttribute('data-co-id');

            $type = ContentObjectRendition::VIEW_INLINE;

            $parameters_list = $this->dom_xpath->query('@*', $placeholder);
            $parameters = array();

            foreach ($parameters_list as $parameter) {
                $parameters[$parameter->name] = $parameter->value;
            }

            try {
                $object = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                    ContentObject::class_name(),
                    $contentObjectId);

                if (!$object instanceof ContentObject) {
                    continue;
                }

                $securityCode = $placeholder->getAttribute('data-security-code');
                if(empty($securityCode) || $securityCode != $object->calculate_security_code())
                {
                    continue;
                }
            } catch (\Exception $exception) {
                continue;
            }

            list($rendition_xpath, $fragment) = $this->getFragment($object, $type, $parameters);

            $this->addNodesToDocumentFragment($fragment, $rendition_xpath->query('//script'));
            $this->addNodesToDocumentFragment($fragment, $rendition_xpath->query('//link'));
            $this->addNodesToDocumentFragment($fragment, $rendition_xpath->query('body/*'));

            $fragment = $this->dom_document->importNode($fragment, true);

            if($placeholder->tagName == 'div') {
                $placeholder->appendChild($fragment);
            }
            else {
                $placeholder->parentNode->insertBefore($fragment, $placeholder);
                $placeholder->parentNode->removeChild($placeholder);
            }
        }
    }

    /**
     * @Deprecated
     */
    protected function processResources()
    {
        $resources = $this->dom_xpath->query('//resource');
        foreach ($resources as $resource) {
            $source = $resource->getAttribute('source');
            // $type = $resource->getAttribute('type');
            // if (! $type)
            // {
            $type = ContentObjectRendition::VIEW_INLINE;
            // }

            $parameters_list = $this->dom_xpath->query('@*', $resource);
            $parameters = array();
            foreach ($parameters_list as $parameter) {
                $parameters[$parameter->name] = $parameter->value;
            }

            try {
                $object = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                    ContentObject::class_name(),
                    $source);

                if (!$object instanceof ContentObject) {
                    continue;
                }
            } catch (\Exception $exception) {
                continue;
            }

            list($rendition_xpath, $fragment) = $this->getFragment($object, $type, $parameters);

            $this->addNodesToDocumentFragment($fragment, $rendition_xpath->query('//script'));
            $this->addNodesToDocumentFragment($fragment, $rendition_xpath->query('//link'));
            $this->addNodesToDocumentFragment($fragment, $rendition_xpath->query('body/*'));

            $fragment = $this->dom_document->importNode($fragment, true);

            $resource->parentNode->insertBefore($fragment, $resource);
            $resource->parentNode->removeChild($resource);
        }
    }

    /**
     * @param $object
     * @param $type
     * @param $parameters
     * @return array
     */
    protected function getFragment($object, $type, $parameters): array
    {
        $descriptionRendition = ContentObjectRenditionImplementation::factory(
            $object,
            ContentObjectRendition::FORMAT_HTML,
            $type,
            $this)->render($parameters);

        $rendition = new DOMDocument();
        $rendition->loadHTML($descriptionRendition);

        $rendition_xpath = new DOMXPath($rendition);

        $fragment = $rendition->createDocumentFragment();
        return array($rendition_xpath, $fragment);
    }
}
