<?php

namespace App\Model;

/**
 * Description of PhotoManager
 * nejde vytvorit fotolagerie s poctem fotek 0
 * pri mazani fotek zustane alespon 1 fotka
 */
class PhotoManager
{
  /** @var \Nette\Database\Context */
  private $database;

  /** @var string */
  private $dirPath;

  
  public function __construct(\Nette\Database\Context $connection)
  {
    $this->database = $connection;
    $this->dirPath = $dir = __DIR__ .  '/../../www';
  }
  

  /**
   * ulozeni zaznamu do fotogalerie
   * @param string $name
   * @param int $videoId
   * @param string $folder
   * @param string $seoTitle
   * @param string $seoDescription
   * @param string $seoKeywords
   * @param int $count
   * @return int|false
   */
  public function addPhotogallery($name, $seoTitle, $seoDescription, $seoKeywords, $count)
  {
    $data = array(
      //'videos_id'       => $videoId,
      'name'            => $name,
      'seo_title'       => $seoTitle,
      'seo_description' => $seoDescription,
      'seo_keywords'    => $seoKeywords,
      'count'           => $count,
      'date_create'     => date('Y-m-d H:i:s'),
      'active'          => 1
    );
    
    try {
      $this->database->query('INSERT INTO photogallery', $data);
    } catch (\Exception $e) {
      return FALSE;
    }
    
    return $this->database->getInsertId();
  }
  
  /**
   * vrati pole obrazku z retezce, ve kterem jsou obrazky oddelene carkou
   * @param string $string
   * @return array
   */
  public function getPhotosFromString($string)
  {
    $uploadContent = explode(',', $string);
    $images = array();
    $directory = '';
    
    foreach ($uploadContent as $content) {
      $filename = DIR_ROOT.$content;
      
      // pokud je vybran adresar, vezmeme z neho jen vsechny soubory
      if (file_exists($filename) && is_dir($filename)) {
        $directory = $content;
        foreach (new \DirectoryIterator($filename) as $file) {
          if ($file->isDot() || $file->isDir()) { continue; }
          if ($this->isImage($file->getPathname())) {
            $images[] = $file->getPathname();
          }
        }
      } elseif (file_exists($filename) && !is_dir($filename) && $this->isImage($filename)) {
        
        if ($directory == '') {
          // checkbox je jen u obrazku z adresare
          $images[] = $filename;
        } elseif (strpos($filename, $directory) === FALSE) {
          // checkbox je u adresare a zaroven u obrazku v danem adresari, checkboxy u obrazku ignorujeme, protoze byl vybran cely adresar
          continue;
        }
      }
    }
    
    return $images;
  }
  
  /**
   * zda je soubor typu gif, jpeg, png nebo bmp
   * @param string $filename
   * @return boolean
   */
  public function isImage($filename)
  {
    $mimeTypes = array(
      'image/gif', 'image/jpeg', 'image/png', 'image/bmp'
    );
    
    $imageSize = @getimagesize($filename);
    if (in_array($imageSize['mime'], $mimeTypes)) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * pridani fotek
   * pokud existuje fotka se stejnym jmenem, aktualizuje se obrazek na filesystemu, ale v DB zustane puvodni zaznam
   * @param array $photos
   * @param int $photogalleryId
   * @param string $fullImagePath
   * @param string $previewImagePath
   */
  public function addPhotos($photos, $photogalleryId, $fullImagePath = '', $previewImagePath = '')
  {
    // cesty k souboru
    $dir = __DIR__ .  '/../../www';
    
    if ($fullImagePath == '') {
      $fullImagePath = DIR_ROOT . '/www/photos/photos' . $photogalleryId . '/full/';
      !file_exists($fullImagePath) ? mkdir($fullImagePath, 0777, TRUE) : '';
    } else {
      $fullImagePath = $dir . $fullImagePath . '/';
    }
    
    if ($previewImagePath == '') {
      $previewImagePath = DIR_ROOT . '/www/photos/photos' . $photogalleryId . '/preview/';
    } else {
      $previewImagePath = $dir . $previewImagePath . '/';
    }
    
    foreach ($photos as $photo) {
      $fileInfo = new \SplFileInfo($photo);
      $photoName = $fileInfo->getFilename();
      $fullImageFilePath = $fullImagePath . $photoName;

      // zkopirujeme obrazek v puvodni velikosti
      copy($fileInfo->getPathname(), $fullImageFilePath);

      // vytvorime nahledy obrazku
      if (file_exists($fullImageFilePath)) {
        /*
        $image = \Nette\Image::fromFile($fullImageFilePath);
        $image->resize(960, 540, \Nette\Utils\Image::ENLARGE | \Nette\Utils\Image::STRETCH);
        $image->sharpen();
        */

        file_exists($previewImagePath) ? '' : mkdir($previewImagePath, 0777, TRUE);
        
        $simpleImage = new \SimpleImage();
        $simpleImage->load($fullImageFilePath);
        
        if ($simpleImage->getWidth() > $simpleImage->getHeight()) {
          //$simpleImage->cutFromCenter(960, 540);
          $simpleImage->cutFromCenter(800, 450);
        } else {
          $simpleImage->maxareafill(800, 450, 240, 240, 240);
        }
        
        $previewImageFilePath = $previewImagePath . $photoName;
        $isSavedImage = $simpleImage->save($previewImageFilePath);
        
        if (!$isSavedImage) {
          // TODO nepodarilo se vytvorit nahled - log ?
        }

        // ulozime fotky TODO multiinsert, transakce ?
        $this->addPhoto($photogalleryId, '/photos/photos' . $photogalleryId . '/full/' . $photoName, '/photos/photos' . $photogalleryId . '/preview/' . $photoName);

      } else {
        // TODO nepodarilo se ulozit vyhcozi obrazek na filesystem
      }          
    }
  }
  
  
  public function addPhoto($photogalleryId, $urlFullFilePath, $urlPreviewFilePath)
  {
    $data = array(
      'photogallery_id'   => $photogalleryId,
      'full_filepath'     => $urlFullFilePath,
      'preview_filepath'  => $urlPreviewFilePath,
    );
    
    $this->database->query('INSERT IGNORE INTO photos', $data);
  }
  
  
  public function getCountPhotosByPhotogallery($photogalleryId)
  {
    try {
     $countRow = $this->database->query('SELECT COUNT(id) AS count FROM photos WHERE photogallery_id = ?', $photogalleryId)->fetch();
    } catch (\Exception $e) {
      return FALSE;
    }
    
    return $countRow->count;
  }

  
  public function addVideosPhotogallery($videoId, $photogalleryId)
  {
    $data = array(
      'videos_id'       => $videoId,
      'photogallery_id' => $photogalleryId,
    );
    
    $this->database->query('INSERT INTO videos_photogallery', $data);
  }


  public function getPhotosgalleryList($from, $count)
  {
    $query = $this->database->query('
      SELECT
        photogallery.id,
        photogallery.name,
        photogallery.count,
        photogallery.active,
        videos_photogallery.videos_id AS video
      FROM
        photogallery
      LEFT JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
      ORDER BY photogallery.date_create DESC
      LIMIT ?, ?
    ', $from, $count)->fetchAll();
    
    return $query;
  }
  
  
  public function getPhotosgalleryListCount()
  {
    $query = $this->database->query('
      SELECT
        COUNT(photogallery.id) AS count
      FROM photogallery
      LEFT JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
    ')->fetch();
    
    return $query->count;
  }
  
  
  public function getPhotosgalleryListWithVideo($from, $count)
  {
    $query = $this->database->query('
      SELECT
        photogallery.id,
        photogallery.name,
        photogallery.count,
        photogallery.active,
        videos_photogallery.videos_id AS video
      FROM photogallery
      INNER JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
      ORDER BY photogallery.date_create DESC
      LIMIT ?, ?
    ', $from, $count)->fetchAll();
     
    return $query;
  }
  
  
  public function getPhotosgalleryListWithVideoCount()
  {
    $query = $this->database->query('
      SELECT
        COUNT(photogallery.id) AS count
      FROM photogallery
      INNER JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
    ')->fetch();
     
    return $query->count;
  }
  
  
  public function getPhotosgalleryListWithoutVideo($from, $count)
  {
    $query = $this->database->query('
      SELECT
        photogallery.id,
        photogallery.name,
        photogallery.count,
        photogallery.active,
        videos_photogallery.videos_id AS video
      FROM
        photogallery
      LEFT JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
      WHERE 1
        AND videos_photogallery.videos_id IS NULL
      ORDER BY photogallery.date_create DESC
      LIMIT ?, ?
    ', $from, $count)->fetchAll();
     
    return $query;
  }
  
  
  public function getPhotosgalleryListWithoutVideoCount()
  {
    $query = $this->database->query('
      SELECT
        COUNT(photogallery.id) AS count
      FROM photogallery
      LEFT JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
      WHERE 1
        AND videos_photogallery.videos_id IS NULL
    ')->fetch();
     
    return $query->count;
  }
  

  /**
   * informace o fotogalerii vcetne id videa
   * @param int $id
   * @return type
   */
  public function getPhotogallery($id)
  {
    $query = $this->database->query('
      SELECT
        photogallery.id,
        videos_photogallery.videos_id AS video_id,
        photogallery.name,
        photogallery.seo_title,
        photogallery.seo_description,
        photogallery.seo_keywords,
        photogallery.count,
        photogallery.active
      FROM photogallery
      LEFT JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
      WHERE photogallery.id = ?
    ', intval($id))->fetch();
    
    return $query;
  }

  /**
   * informace o fotogalerii vcetne cesty k fotkam
   * @param int $id
   * @return type
   */
  public function getPhotogalleryPhotosPath($id)
  {
    $query = $this->database->query('
      SELECT
        photogallery.id,
        name,
        seo_title,
        seo_description,
        seo_keywords,
        count,
        active,
        date_create,
        full_filepath,
        preview_filepath
      FROM photogallery
      LEFT JOIN photos ON photos.photogallery_id = photogallery.id
      WHERE photogallery_id = ?
      LIMIT 1
    ', intval($id))->fetch();
    
    return $query;
  }
  
  
  public function getPhotogalleryForDelete($photogalleryId)
  {
    $query = $this->database->query('
      SELECT
        photogallery.id,
        full_filepath,
        preview_filepath,
        videos_id AS video_id
      FROM photogallery
      LEFT JOIN photos ON photos.photogallery_id = photogallery.id
      LEFT JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
      WHERE photogallery.id = ?
      LIMIT 1
    ', intval($photogalleryId))->fetch();
    
    return $query;
  }
  
  
  
  
  public function setPhotogallery($photogalleryId, $name, $seoDescription, $seoTitle, $seoKeywords, $active)
  {
    $data = array(
      'name'            => $name,
      'seo_title'       => $seoTitle,
      'seo_description' => $seoDescription,
      'seo_keywords'    => $seoKeywords,
      'active'          => $active,
    );
    
    $this->database->query('UPDATE photogallery SET ? WHERE id = ?', $data, $photogalleryId);
  }
  
  
  public function setVideosPhotogallery($videoId, $photogalleryId)
  {
    $this->database->query('UPDATE videos_photogallery SET ? WHERE photogallery_id = ?',
      array('videos_id' => $videoId), $photogalleryId
    );
  }
  
  public function addVideoToPhotogallery($videoId, $photogalleryId)
  {
    $data = array(
      'videos_id'       => $videoId,
      'photogallery_id' => $photogalleryId,
    );
    
    $this->database->query('INSERT INTO videos_photogallery', $data);
  }
  
  public function deleteVideoFromPhotogallery($photogalleryId)
  {
    $this->database->query('DELETE FROM videos_photogallery WHERE photogallery_id = ?', $photogalleryId);
  }
  
  
  /**
   * pocet videi k fotogalerii
   * @param int $photogalleryId
   * @return int
   */
  public function getCountVideos($photogalleryId)
  {
    $query = $this->database->query('SELECT COUNT(videos_id) AS count FROM videos_photogallery WHERE photogallery_id = ?',
      $photogalleryId
    )->fetch();

    $count = $query->count;
    
    if ($count > 1) {
      throw new \Exception('K fotogalerii je prirazeno vice videii!');
    }
    
    return $count;
  }
  
  /**
   * pocet fotek ve fotogalerii
   * @param int $photogalleryId
   * @return int
   */
  public function getCountPhotos($photogalleryId)
  {
    $query = $this->database->query('SELECT count(id) AS count FROM photos WHERE photogallery_id = ?', $photogalleryId)->fetch();
    return $query->count;
  }

  /**
   * zmeni pocet fotek u fotogalerie
   * @param int $count
   * @param int $photogalleryId
   * @return type
   */
  public function setCountPhotos($count, $photogalleryId)
  {
    $query = $this->database->query('UPDATE photogallery SET count = ? WHERE id = ?', $count, $photogalleryId);
    return $query;
  }
  
  
  
  /**
   * odstrani fotogalerii
   * @param int $photogalleryId
   * @return boolean
   */
  public function deletePhotogallery($photogalleryId)
  {
    $photogallery = $this->getPhotogalleryForDelete($photogalleryId);
    
    if (count($photogallery) == 0) { return FALSE; }

    $filePathList = explode('/', $photogallery->full_filepath);
    $filePath = $filePathList[1] . '/' . $filePathList[2];
    $dirParentFilePath = $this->dirPath . '/' . $filePath;
    $dirFilePath = $dirParentFilePath . '/' . $photogalleryId;
    
    // uklidime stopy po fotogalerii
    if (is_dir($dirFilePath)) { $this->deleteDir($dirFilePath); }
    if ($this->isEmptyDir($dirParentFilePath)) { rmdir($dirParentFilePath); }
    
    // smazeme fotky z db
    $this->database->query('DELETE FROM photos WHERE photogallery_id = ?', $photogalleryId);
    $photogallery->video_id !== NULL ? $this->database->query('DELETE FROM videos_photogallery WHERE photogallery_id = ?', $photogalleryId) : '';
    $this->database->query('DELETE FROM photogallery WHERE id = ?', $photogalleryId);
    
    return TRUE;
  }
  
  /**
   * smaze vsechny podadresare a soubory
   * @param string $dir
   */
  private function deleteDir($dir)
  {
    $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new \RecursiveIteratorIterator($it,
                 \RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
        if ($file->isDir()){
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
  }
  
  /**
   * zda je adresar prazdny
   * @param string $directory
   * @return boolean
   */
  private function isEmptyDir($directory)
  {
    $files = scandir($directory);
    
    foreach ($files as $key => $file) {
      if ($file == '.' || $file == '..') {
        unset($files[$key]);
      }
    }
    
    return count($files) == 0 ? TRUE : FALSE;
  }
  
  
  /************* photos *******************/
  
  public function getPhotos($photogalleryId, $from, $count)
  {
    $query = $this->database->query('
      SELECT id, top, preview_filepath FROM photos WHERE photogallery_id = ? ORDER BY id DESC LIMIT ?, ?
    ', intval($photogalleryId), $from, $count)->fetchAll();
    
    return $query;
  }
  
  public function getPhoto($photoId)
  {
    $query = $this->database->query('
      SELECT id, photogallery_id, full_filepath, preview_filepath FROM photos WHERE id = ?
    ', $photoId)->fetch();
    
    return $query;
  }
  
  
  public function getPhotosgalleryCount($photogalleryId)
  {
    $query = $this->database->query('SELECT count FROM photogallery WHERE id = ?', $photogalleryId)->fetch();
    return $query->count;
  }
  
  
  
  
  public function deletePhoto($photoId)
  {
    $photo = $this->getPhoto($photoId);
    
    if ($photo) {
      $countPhotos = $this->getCountPhotos($photo->photogallery_id);
      
      if ($countPhotos > 1) {
        
        $fullImage = $this->dirPath . $photo->full_filepath;
        $previewImage = $this->dirPath . $photo->preview_filepath;
        
        file_exists($fullImage) ? unlink($fullImage) : '';
        file_exists($previewImage) ? unlink($previewImage) : '';
        
        $this->database->query('DELETE FROM photos WHERE photogallery_id = ? AND id = ?', $photo->photogallery_id, $photoId);
        
        $newCountPhotos = $this->getCountPhotos($photo->photogallery_id);
        $this->setCountPhotos($newCountPhotos, $photo->photogallery_id);
      }
    }
  }

  
  /**
   * nastaveni fotky jako hlavni
   * @param int $photoId
   */
  public function topPhoto($photoId)
  {
    $photo = $this->getPhoto($photoId);
    
    if ($photo) {
      $topPhoto = $this->getTopPhotoByPhotogallery($photo->photogallery_id);
      $topPhoto ? $this->setTopPhoto($topPhoto, 0) : '';
      $this->setTopPhoto($photo, 1);
    }
  }
  
  /**
   * vrati fotku ve fotogalerii, ktera je nastavena jako hlavni
   * @param int $photogalleryId
   * @return Nette\Database\Row
   */
  public function getTopPhotoByPhotogallery($photogalleryId)
  {
    $query = $this->database->query('
      SELECT
        id,
        photogallery_id,
        top,
        full_filepath,
        preview_filepath
      FROM photos
      WHERE top = 1 AND photogallery_id = ?
    ', $photogalleryId)->fetch();
    
    return $query;
  }
  
  /**
   * nastaveni parametru top u fotky
   * @param Nette\Database\Row $photo
   * @param int $value
   * @return bool
   */
  public function setTopPhoto($photo, $value)
  {
    $result = $this->database->query('UPDATE photos SET top = ? WHERE id = ? AND photogallery_id = ?', $value, $photo->id, $photo->photogallery_id);
    return $result;
  }
  
  public function getPhotosgalleryRecent($count)
  {
    $query = $this->database->query('
      SELECT
        photos.id AS photo_id,
        photogallery.id AS photogallery_id,
        photogallery.name,
        photos.preview_filepath
      FROM photogallery
      LEFT JOIN photos ON photos.photogallery_id = photogallery.id
      WHERE active = 1
      GROUP BY photogallery.id
      ORDER BY photogallery.id DESC, photos.id ASC
      LIMIT ?
    ', $count)->fetchAll();
    return $query;
  }
  
  
  /**
   * REALLY NEED ?
   * @param type $videoId
   * @return type
   */
  public function GetPhotogalleryByVideo($videoId)
  {
    $query = $this->database->query('
      SELECT
        photogallery.id,
        photogallery.name,
        photogallery.seo_title,
        photogallery.seo_description,
        photogallery.seo_keywords,
        photogallery.count
      FROM photogallery
      LEFT JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
      WHERE videos_photogallery.videos_id = ? AND active = 1
      LIMIT 1  
    ', $videoId)->fetch();
    
    return $query;
  }
  
  /**
   * TODO LIMIT
   * @param type $videoId
   * @return type
   */
  public function getPhotosByVideo($videoId, $count = NULL)
  {
    $statement = '
      SELECT
        photos.id AS photo_id,
        photogallery.id AS photogallery_id,
        videos_photogallery.videos_id AS video_id,
        photogallery.count,
        photos.full_filepath,
        photos.preview_filepath
      FROM photos
      INNER JOIN videos_photogallery ON videos_photogallery.photogallery_id = photos.photogallery_id
      INNER JOIN photogallery ON photogallery.id = videos_photogallery.photogallery_id
      WHERE videos_photogallery.videos_id = ? AND photogallery.active = 1
      ORDER BY photogallery.id DESC, photos.id
    ';
    
    if ($count) {
      $statement = $statement . ' LIMIT ?';
      $query = $this->database->query($statement, $videoId, $count)->fetchAll();
    } else {
      $query = $this->database->query($statement, $videoId)->fetchAll();
    }
    
    return $query;
  }
  
  

  public function getPhotosByPhotogallery($photogalleryId, $from, $count)
  {
    $query = $this->database->query('
      SELECT
        preview_filepath AS preview_url,
        full_filepath AS full_url,
        photogallery.name AS photogallery_name,
        photogallery.count,
        videos_photogallery.videos_id AS video_id
      FROM photos
      INNER JOIN photogallery ON photogallery.id = photos.photogallery_id
      LEFT JOIN videos_photogallery ON videos_photogallery.photogallery_id = photogallery.id
      WHERE photogallery.active = 1 AND photos.photogallery_id = ?
      ORDER BY photos.id DESC
      LIMIT ?, ?
    ', $photogalleryId, $from, $count)->fetchAll();
            
    return $query;
  }

  
}
