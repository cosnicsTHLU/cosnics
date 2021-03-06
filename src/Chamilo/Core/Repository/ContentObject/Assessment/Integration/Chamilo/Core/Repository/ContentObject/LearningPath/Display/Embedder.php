<?php
namespace Chamilo\Core\Repository\ContentObject\Assessment\Integration\Chamilo\Core\Repository\ContentObject\LearningPath\Display;

use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Embedder\Type\ComplexContentObjectEmbedder;

/**
 *
 * @package core\repository\content_object\assessment\integration\core\repository\content_object\learning_path\display
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class Embedder extends ComplexContentObjectEmbedder
{

    /**
     *
     * @see \core\repository\content_object\learning_path\display\Embedder::run()
     */
    public function run()
    {
        return $this->render();
    }
}
