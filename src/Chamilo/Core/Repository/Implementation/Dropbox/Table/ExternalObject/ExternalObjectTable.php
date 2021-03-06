<?php
namespace Chamilo\Core\Repository\Implementation\Dropbox\Table\ExternalObject;

use Chamilo\Core\Repository\Implementation\Dropbox\Manager;
use Chamilo\Libraries\Format\Table\Extension\DataClassTable\DataClassTable;

class ExternalObjectTable extends DataClassTable
{
    const TABLE_IDENTIFIER = Manager::PARAM_EXTERNAL_REPOSITORY_ID;
}
