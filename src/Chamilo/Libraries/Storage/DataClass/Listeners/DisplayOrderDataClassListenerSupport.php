<?php
namespace Chamilo\Libraries\Storage\DataClass\Listeners;

/**
 * Interface that makes sure that dataclasses who use the display order data class listener also support the correct
 * functionality
 * 
 * @author Sven Vanpoucke - Hogeschool Gent
 */
interface DisplayOrderDataClassListenerSupport
{

    /**
     * Returns the property for the display order
     * 
     * @abstract
     *
     *
     * @return string
     */
    public function get_display_order_property();

    /**
     * Returns the properties that define the context for the display order (the properties on which has to be limited)
     * 
     * @abstract
     *
     *
     * @return Condition
     */
    public function get_display_order_context_properties();
}
