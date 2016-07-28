<?php

namespace App\Model;

/**
 * Description of ContentManager
 */
class ContentManager
{
  /** @var \Nette\Database\Context */
  private $database;
  
  
  public function __construct(\Nette\Database\Context $connection)
  {
    $this->database = $connection;
  }
  
  
  public function getAll($from, $count)
  {
    $query = $this->database->query("
      SELECT
        id,
        name,
        video_full_seconds AS seconds_count,
        date_create,
        CONCAT('/videos/video', id, '/poster.jpg') AS url,
        CONCAT('/videos/video', id, '/poster.jpg') AS url_top,
        'video' AS type
      FROM videos
      WHERE state = 'ready' AND active = 1
      UNION ALL
      SELECT 
        photogallery.id,
        photogallery.name,
        photogallery.count,
        photogallery.date_create,
        (SELECT preview_filepath FROM photos WHERE photos.photogallery_id = photogallery.id ORDER BY photos.id DESC LIMIT 1) AS preview_filepath,
        (SELECT preview_filepath FROM photos WHERE photos.photogallery_id = photogallery.id AND photos.top = 1 LIMIT 1) AS top_preview_filepath,
        'photogallery' AS type
      FROM photogallery
      WHERE active = 1
      GROUP BY photogallery.id
      ORDER BY date_create DESC
      LIMIT ?, ?
    ", $from, $count)->fetchAll();
            
    return $query;
  }
  
  public function getVideos($from, $count, $where = FALSE)
  {
    $query = $this->database->query("
      SELECT
        id,
        name,
        video_full_seconds AS seconds_count,
        CONCAT('/videos/video', id, '/poster.jpg') AS url,
        'video' AS type
      FROM videos
      WHERE state = 'ready' AND active = 1" . ($where ? ' AND '. $where : '') . " ORDER BY date_create DESC
      LIMIT ?, ?
    ", $from, $count)->fetchAll();

    return $query;
  }
  
  public function getPhotosgallery($from, $count)
  {
    $query = $this->database->query("
      SELECT 
        photogallery.id,
        photogallery.name,
        photogallery.count AS seconds_count,
        (SELECT photos.preview_filepath FROM photos WHERE photos.photogallery_id = photogallery.id ORDER BY photos.id DESC LIMIT 1) AS url,
        (SELECT photos.preview_filepath FROM photos WHERE photos.photogallery_id = photogallery.id AND photos.top = 1 LIMIT 1) AS url_top,
        'photogallery' AS type
      FROM photogallery
      WHERE active = 1
      GROUP BY photogallery.id
      ORDER BY date_create DESC
      LIMIT ?, ?
    ", $from, $count)->fetchAll();
    
    return $query;
  }
  
  
  public function getAllCount()
  {
    $query = $this->database->query("
      SELECT (
        SELECT COUNT(*) FROM photogallery WHERE active = 1
      ) + (
        SELECT COUNT(*) FROM videos WHERE state = 'ready' AND active = 1
      ) AS count
    ")->fetch();

    return $query->count;
  }
  
  public function getVideosCount()
  {
    $query = $this->database->query("
      SELECT COUNT(*) AS count FROM videos WHERE state = 'ready' AND active = 1
    ")->fetch();
    
    return $query->count;
  }
  
  public function getPhotosgalleryCount()
  {
    $query = $this->database->query('
      SELECT COUNT(*) AS count FROM photogallery WHERE active = 1
    ')->fetch();
    
    return $query->count;
  }
  
  
}
