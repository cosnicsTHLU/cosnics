<?php
namespace Chamilo\Core\Repository\ContentObject\Survey\Page\Question\Matrix\Common\Export\Cpo;

use Chamilo\Core\Repository\Common\Export\ContentObjectExport;
use Chamilo\Core\Repository\ContentObject\Survey\Page\Question\Matrix\Common\Export\CpoExportImplementation;

/**
 *
 * @package repository.content_object.survey_matrix_question
 * @author Eduard Vossen
 * @author Magali Gillard
 * @author Hans De Bisschop
 */
class CpoDefaultExportImplementation extends CpoExportImplementation
{

    function render()
    {
        ContentObjectExport::launch($this);
        
        $content_object_node = $this->get_context()->get_content_object_sub_node(
            CpoExportImplementation::SURVEY_MATRIX_QUESTION_EXPORT, 
            $this->get_content_object()->get_id());
        
        $dom_document = $this->get_context()->get_dom_document();
        
        $options_node = $content_object_node->appendChild(
            $dom_document->createElement(CpoExportImplementation::OPTIONS_NODE));
        $options = $this->get_content_object()->get_options();
        
        // options
        while ($option = $options->next_result())
        {
            
            $option_node = $options_node->appendChild(
                $dom_document->createElement(CpoExportImplementation::OPTION_NODE));
            
            $value = $option_node->appendChild($dom_document->createAttribute('id'));
            $value->appendChild($dom_document->createTextNode($option->get_id()));
            
            $display_order = $option_node->appendChild($dom_document->createAttribute('display_order'));
            $display_order->appendChild($dom_document->createTextNode($option->get_display_order()));
            
            $value = $option_node->appendChild($dom_document->createAttribute('value'));
            $value->appendChild($dom_document->createTextNode($option->get_value()));
        }
        
        $matches_node = $content_object_node->appendChild(
            $dom_document->createElement(CpoExportImplementation::MATCHES_NODE));
        $matches = $this->get_content_object()->get_matches();
        
        // matches
        while ($match = $matches->next_result())
        {
            
            $match_node = $matches_node->appendChild(
                $dom_document->createElement(CpoExportImplementation::MATCH_NODE));
            
            $value = $match_node->appendChild($dom_document->createAttribute('id'));
            $value->appendChild($dom_document->createTextNode($match->get_id()));
            
            $display_order = $match_node->appendChild($dom_document->createAttribute('display_order'));
            $display_order->appendChild($dom_document->createTextNode($match->get_display_order()));
            
            $value = $match_node->appendChild($dom_document->createAttribute('value'));
            $value->appendChild($dom_document->createTextNode($match->get_value()));
        }
    }
}