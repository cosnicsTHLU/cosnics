<?php
namespace Chamilo\Libraries\Architecture\Interfaces;

/**
 * A class implements the <code>Hashable</code> interface to indicate that it can be hashed to a unique MD5 string
 * 
 * @package Chamilo\Libraries\Architecture\Interfaces
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
interface Hashable
{

    /**
     *
     * @return string
     */
    public function hash();

    /**
     *
     * @return string[]
     */
    public function getHashParts();

    /**
     *
     * @param string $hash
     */
    public function setHash($hash);

    /**
     *
     * @return string
     */
    public function getHash();
}
