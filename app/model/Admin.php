<?php

namespace App\Model;

use Nette;

class Admin
{
	

	/** @var Nette\Database\Context */
	private $database;

    /** @var UserManager */
    private $userManager;
    
    /** @var VideoManager */
    private $videoManager;
    
    /** @var PhotoManager */
    private $photoManager;
            
    /** @var FFMpegManager */
    private $ffmpegManager;
    
    /** @var TariffManager */
    private $tariffManager;
    
    /** @var TextManager */
    private $textManager;
    
    /** @var EmailManager */
    private $emailManager;
    
    private $user_id;
    private $data;
    
    
    private $page_size;
    
	public function __construct(Nette\Database\Context $database, $user_id)
	{
		$this->database = $database;
        
    $this->userManager = new UserManager($this->database);
    $this->videoManager = new VideoManager($this->database);        
    $this->photoManager = new PhotoManager($this->database);
    $this->ffmpegManager = new FFMpegManager();
    $this->tariffManager = new TariffManager($this->database);
    $this->textManager = new TextManager($this->database);
    $this->emailManager = new EmailManager($this->database);

    $this->videoManager->SetFilter(true, true, false, false);

    $this->user_id = $user_id;

    $this->data = $this->userManager->get($this->user_id);
    $this->page_size = 10;        
	}
   
    
    public function IsLoggedIn()
    {
        if ($this->user_id == 0) return false;
        return true;
    }
   
    
    public function GetPageSize()
    {
        return $this->page_size;
    }
    
    public function GetTariffs()
    {
        return $this->tariffManager->GetTariffs();
    }
    
    public function SetTariff($tariff_id, $name, $period, $price, $zombaio_pricing_id)
    {
        if (!$this->IsLoggedIn()) return false;
        return $this->tariffManager->SetTariff($tariff_id, $name, $period, $price, $zombaio_pricing_id);
    }
    
    
    public function GetEmailSetting()
    {
        return $this->emailManager->GetEmailSetting();
    }
    
    public function SetEmailSetting($sender, $sender_name, $response, $smtp,
            $smtp_host, $smtp_protocol, $smtp_secure, $smtp_username, $smtp_password,
            $system_email)
    {
        return $this->emailManager->SetEmailSetting($sender, $sender_name, $response, $smtp, 
                $smtp_host, $smtp_protocol, $smtp_secure, $smtp_username, $smtp_password,
                $system_email);
    }
    
    public function GetTexts()
    {
        return $this->textManager->GetTexts();
    }
    
    public function GetText($page_id)
    {
        return $this->textManager->GetText($page_id);
    }
    
    public function SetText($page_id, $seo_title, $seo_description, $seo_keywords, $text, $text2, $text3)
    {
        if (!$this->IsLoggedIn()) return false;
        return $this->textManager->SetText($page_id, $seo_title, $seo_description, $seo_keywords, $text, $text2, $text3);
    }
    
    public function GetVideosAllPageCount()
    {
        $count = $this->videoManager->GetVideoListCount(true, true, true);
        $page_count = ceil($count/$this->page_size);
        return $page_count;
    }
    
    public function GetVideosAll($page)
    {     
        $offset = $page*$this->page_size;
        $count = $this->page_size;
        $videos = $this->videoManager->GetVideoList(true, true, true, $offset, $count);
        return $videos;
    }
    
    public function GetVideosTrailerPageCount()
    {
        $count = $this->videoManager->GetVideoListCount(true, false, false);
        $page_count = ceil($count/$this->page_size);
        return $page_count;
    }
    
    public function GetVideosTrailer($page)
    {
        $offset = $page*$this->page_size;
        $count = $this->page_size;
        $videos = $this->videoManager->GetVideoList(true, false, false, $offset, $count);
        return $videos;
    }
    
    public function GetVideosTopPageCount()
    {
        $count = $this->videoManager->GetVideoListCount(false, true, false);
        $page_count = ceil($count/$this->page_size);
        return $page_count;
    }
    
    public function GetVideosTop($page)
    {
        $offset = $page*$this->page_size;
        $count = $this->page_size;
        $videos = $this->videoManager->GetVideoList(false, true, false, $offset, $count);
        return $videos;
    }
    
    public function GetVideo($id) 
    {
        return $this->videoManager->GetVideo($id);
    }       
    
    public function TrailerExists()
    {
        return $this->videoManager->TrailerExists();
    }
    
    public function CreateVideo($name, $description, $seo_title, $seo_description, $seo_keywords, $type, $video_filename, $trailer_filename, $code)
    {
        if (!$this->IsLoggedIn()) return false;
        
        set_time_limit(0);
        ini_set('max_execution_time', 36000);
        
        
        if (!$name) return false;
        if (!$video_filename || !file_exists($video_filename)) return false;
        
        $id = $this->videoManager->PrepareVideo($name, $description, $seo_title, $seo_description, $seo_keywords, $type, $video_filename, $trailer_filename, $code);
        if (!$id) return false;
        
        $this->ffmpegManager->OpenVideo($video_filename);
        $this->ffmpegManager->VideoCreatePreviews(__DIR__."/../../www/videos/video$id/");     
        $duration = $this->ffmpegManager->VideoGetDuration();
        $this->videoManager->SetVideoDuration($id, $duration);        
        //$video_filename_hd = $this->ffmpegManager->VideoCreateHD();
                
        
        $trailer_filename_hd = "";
        if ($trailer_filename && file_exists($trailer_filename) && $type != "trailer") {
            $this->ffmpegManager->OpenVideo($trailer_filename);
            $duration = $this->ffmpegManager->VideoGetDuration();
            $this->videoManager->SetTrailerDuration($id, $duration);
            //$trailer_filename_hd = $this->ffmpegManager->VideoCreateHD();
        }
        
        //$this->videoManager->FinishVideo($id, $video_filename_hd, $trailer_filename_hd);        
        
        return $id;
    }
    
    public function SetVideo($id, $name, $description, $seo_title, $seo_description, $seo_keywords, $type, $active, $trailer_filename)
    {
        if (!$this->IsLoggedIn()) return false;
        
        set_time_limit(0);
        ini_set('max_execution_time', 36000);
        
        $video = $this->videoManager->GetVideo($id);
        if ($video["video_trailer_4k"]) {
            $trailer_filename = $video["video_trailer_4k"];
            $this->videoManager->SetVideo($id, $name, $description, $seo_title, $seo_description, $seo_keywords, $type, $active, $trailer_filename);
        } else {
            $this->videoManager->SetVideo($id, $name, $description, $seo_title, $seo_description, $seo_keywords, $type, $active, $trailer_filename);
            
            $this->ffmpegManager->OpenVideo($trailer_filename);
            $duration = $this->ffmpegManager->VideoGetDuration();
            $this->videoManager->SetTrailerDuration($id, $duration);
            
            //$trailer_filename_hd = $this->ffmpegManager->VideoCreateHD();

            //$this->videoManager->FinishTrailer($id, $trailer_filename_hd);
        }     
        
        return true;
        
    }
    
    public function SetVideoSequenceUp($id)
    {
        if (!$this->IsLoggedIn()) return false;
        $this->videoManager->SetVideoSequenceUp($id);
    }
    
    public function SetVideoSequenceDown($id)
    {
        if (!$this->IsLoggedIn()) return false;
        $this->videoManager->SetVideoSequenceDown($id);
    }
    
    public function SetVideoSequenceUp2($id)
    {
        if (!$this->IsLoggedIn()) return false;
        $this->videoManager->SetVideoSequenceUp2($id);
    }
    
    public function SetVideoSequenceDown2($id)
    {
        if (!$this->IsLoggedIn()) return false;
        $this->videoManager->SetVideoSequenceDown2($id);
    }
    
    public function DeleteVideo($id)
    {
        if (!$this->IsLoggedIn()) return false;
        return $this->videoManager->DeleteVideo($id);
    }
    
    public function CheckVideoInsert($code)
    {
        if (!$this->IsLoggedIn()) return false;
        return $this->videoManager->CheckVideoInsert($code);
    }
    
    
    public function getVideoFetchPairs()
    {
      if (!$this->IsLoggedIn()) return FALSE;
      return $this->videoManager->getVideoFetchPairs();
    }
    
    /**
     * vytvoření fotogalerie
     * @param string $name
     * @param int $videoId
     * @param string $seoTitle
     * @param string $seoDescription
     * @param string $seoKeywords
     * @param array $photosFilepathString
     * @return int|FALSE
     * @throws \Exception
     */
    public function createPhotogalery($name, $videoId, $seoTitle, $seoDescription, $seoKeywords, $photosFilepathString)
    {
      if (!$photosFilepathString) {
        throw new \Exception('No photos uploaded!');
      }
      
      $photos = $this->photoManager->getPhotosFromString($photosFilepathString);
      $photogalleryId = $this->photoManager->addPhotogallery($name, $seoTitle, $seoDescription, $seoKeywords, count($photos));
      if ($photogalleryId) {
        $this->photoManager->addPhotos($photos, $photogalleryId);

        // priradime fotogalerii k videu
        if ($videoId !== NULL) {
          $countPhotos = $this->photoManager->getCountPhotosByPhotogallery($photogalleryId);
          $countPhotos > 0 ? $this->photoManager->addVideosPhotogallery($videoId, $photogalleryId) : '';
        }
        
      } else {
        return FALSE;
      }
      
      return $photogalleryId;
    }
    
    
    public function getPhotosgalleryAll($from, $count)
    {
      $photosgallery = $this->photoManager->getPhotosgalleryList($from, $count);
      return $photosgallery;
    }
    
    public function getPhotosgalleryAllCount()
    {
      return $this->photoManager->getPhotosgalleryListCount();
    }
    
    
    public function getPhotosgalleryWithVideo($from, $count)
    {
      $photosgallery = $this->photoManager->getPhotosgalleryListWithVideo($from, $count);
      return $photosgallery;
    }
    
    public function getPhotosgalleryWithVideoCount()
    {
      return $this->photoManager->getPhotosgalleryListWithVideoCount();
    }
            
    
    public function getPhotosgalleryWithoutVideo($from, $count)
    {
      $photogallery = $this->photoManager->getPhotosgalleryListWithoutVideo($from, $count);
      return $photogallery;
    }
    
    
    public function getPhotosgalleryWithoutVideoCount()
    {
      return $this->photoManager->getPhotosgalleryListWithoutVideoCount();
    }
    
    
    public function getPhotogallery($id) 
    {
      return $this->photoManager->getPhotogallery($id);
    }    
    
    
    public function getVideoByPhotogallery($photogalleryId)
    {
      return $this->videoManager->getVideoByPhotogallery($photogalleryId);
    }
    
    
    /**
     * update photo gallery
     * @param type $photogalleryId
     * @param type $name
     * @param type $videoId
     * @param type $seoTitle
     * @param type $seoDescription
     * @param type $seoKeywords
     * @param type $photosFilepathString
     */
    public function setPhotogallery($photogalleryId, $videoId, $name, $seoDescription, $seoTitle, $seoKeywords, $active, $photosFilepathString)
    {
      if (!$this->IsLoggedIn()) { return FALSE; }
        
      set_time_limit(0);
      ini_set('max_execution_time', 36000);

      $this->photoManager->setPhotogallery($photogalleryId, $name, $seoDescription, $seoTitle, $seoKeywords, $active);

      $countVideos = $this->photoManager->getCountVideos($photogalleryId);

      if ($countVideos == 1 && $videoId !== NULL) {
        $this->photoManager->setVideosPhotogallery($videoId, $photogalleryId);
      } elseif ($countVideos == 1 && $videoId === NULL) {
        $this->photoManager->deleteVideoFromPhotogallery($photogalleryId);
      } elseif ($countVideos == 0 && $videoId !== NULL) {
        $this->photoManager->addVideoToPhotogallery($videoId, $photogalleryId);
      }

      // pridani fotek
      $photos = $this->photoManager->getPhotosFromString($photosFilepathString);
      if ($photos) {
        $photogallery = $this->photoManager->getPhotogalleryPhotosPath($photogalleryId);

        $this->photoManager->addPhotos($photos, $photogalleryId, dirname($photogallery->full_filepath), dirname($photogallery->preview_filepath));
        $countPhotos = $this->photoManager->getCountPhotos($photogalleryId);
        $this->photoManager->setCountPhotos($countPhotos, $photogalleryId);
      }

      // TODO - set false
      return TRUE;
    }
    
    
    public function DeletePhotogallery($photogalleryId)
    {
      if (!$this->IsLoggedIn()) return false;
      return $this->photoManager->deletePhotogallery($photogalleryId);
    }
    
    
    public function getPhotogalleryPages()
    {
      if (!$this->IsLoggedIn()) return false;
      return $this->photoManager->getPages();
    }
    
    
    /**** photos ***************/
    
    public function getPhotos($photogalleryId, $from, $count)
    {
      return $this->photoManager->getPhotos($photogalleryId, $from, $count);
    }
    
    public function getPhotosgalleryCount($photogalleryId)
    {
      return $this->photoManager->getPhotosgalleryCount($photogalleryId);
    }
    
    public function deletePhoto($photoId)
    {
      if (!$this->IsLoggedIn()) { return false; }
      return $this->photoManager->deletePhoto($photoId);
    }
    
    public function topPhoto($photoId)
    {
      if (!$this->IsLoggedIn()) { return false; }
      return $this->photoManager->topPhoto($photoId);
    }
    
}