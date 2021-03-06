<?php
namespace Chamilo\Core\Repository\Common\Import\Youtube;

use Chamilo\Core\Repository\Common\Import\ContentObjectImport;
use Chamilo\Core\Repository\Form\ContentObjectImportForm;
use Chamilo\Libraries\Platform\Translation;

class YoutubeContentObjectImportForm extends ContentObjectImportForm
{
    const PROPERTY_URL = 'url';

    public function build_basic_form()
    {
        parent::build_basic_form();
        $this->add_textfield(self::PROPERTY_URL, Translation::get('Link'));
    }

    public function setDefaults($defaults = array ())
    {
        parent::setDefaults(array(self::PROPERTY_TYPE => ContentObjectImport::FORMAT_YOUTUBE));
    }
}
