<?php
namespace Chamilo\Core\Repository\Ajax\Component;

use Chamilo\Core\Repository\ContentObject\File\Storage\DataClass\File;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\File\Filesystem;
use Chamilo\Libraries\File\ImageManipulation\ImageManipulation;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ThumbnailComponent extends \Chamilo\Core\Repository\Ajax\Manager
{
    const PARAM_WIDTH = 'width';
    const PARAM_HEIGHT = 'height';

    /*
     * (non-PHPdoc) @see common\libraries.AjaxManager::required_parameters()
     */
    public function getRequiredPostParameters()
    {
        return array(\Chamilo\Core\Repository\Manager::PARAM_CONTENT_OBJECT_ID, self::PARAM_WIDTH, self::PARAM_HEIGHT);
    }

    /*
     * (non-PHPdoc) @see common\libraries.AjaxManager::run()
     */
    public function run()
    {
        $contentObject = DataManager::retrieve_by_id(
            ContentObject::class_name(), 
            $this->getPostDataValue(\Chamilo\Core\Repository\Manager::PARAM_CONTENT_OBJECT_ID));
        
        if ($contentObject instanceof File)
        {
            $thumbnail_folder_path = Path::getInstance()->getTemporaryPath(
                \Chamilo\Core\Repository\Manager::context() . '\Thumbnail');
            $thumbnail_file_path = $thumbnail_folder_path . md5($contentObject->get_full_path());
            
            if (! is_file($thumbnail_file_path))
            {
                Filesystem::create_dir($thumbnail_folder_path);
                
                $thumbnail_creator = ImageManipulation::factory($contentObject->get_full_path());
                $thumbnail_creator->scale(
                    $this->getPostDataValue(self::PARAM_WIDTH), 
                    $this->getPostDataValue(self::PARAM_HEIGHT));
                $thumbnail_creator->write_to_file($thumbnail_file_path);
            }
            
            $response = new BinaryFileResponse(
                $thumbnail_file_path, 
                200, 
                array('Content-Type' => $contentObject->get_mime_type()));
            
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $contentObject->get_filename());
            $response->prepare($this->getRequest());
            $response->send();
        }
    }
}
