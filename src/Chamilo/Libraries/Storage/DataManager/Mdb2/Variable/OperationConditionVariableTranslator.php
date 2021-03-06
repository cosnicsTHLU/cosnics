<?php
namespace Chamilo\Libraries\Storage\DataManager\Mdb2\Variable;

use Chamilo\Libraries\Storage\Query\Variable\OperationConditionVariable;

/**
 *
 * @package Chamilo\Libraries\Storage\DataManager\Mdb2\Variable
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class OperationConditionVariableTranslator extends ConditionVariableTranslator
{

    /**
     *
     * @see \Chamilo\Libraries\Storage\Query\Variable\ConditionVariableTranslator::translate()
     */
    public function translate()
    {
        $strings = array();
        
        $strings[] = '(';
        $strings[] = static::render($this->get_condition_variable()->get_left());
        
        switch ($this->get_condition_variable()->get_operator())
        {
            case OperationConditionVariable::ADDITION :
                $strings[] = '+';
                break;
            case OperationConditionVariable::DIVISION :
                $strings[] = '/';
                break;
            case OperationConditionVariable::MINUS :
                $strings[] = '-';
                break;
            case OperationConditionVariable::MULTIPLICATION :
                $strings[] = '*';
                break;
            case OperationConditionVariable::BITWISE_AND :
                $strings[] = '&';
                break;
            case OperationConditionVariable::BITWISE_OR :
                $strings[] = '|';
                break;
        }
        
        $strings[] = static::render($this->get_condition_variable()->get_right());
        $strings[] = ')';
        
        return implode(' ', $strings);
    }
}
