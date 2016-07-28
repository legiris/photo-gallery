<?php

namespace App\Presenters;

/**
 * Description of ContentPresenter
 */
class ContentPresenter extends BasePresenter
{
  
  public function renderDefault($tab = 'all', $page = 1)
  {
    if (!$this->user->IsLoggedIn()) { $this->redirect("Homepage:"); }
    if (!$this->user->IsBeforeExpiration()) { $this->redirect("Homepage:"); }

    $data = array();
    $per_page = 15;
    $count = 0;
    $page = intval($page);
    
    if ($page < 1) { $page = 1; }
    $offset = ($page - 1) * $per_page;

    switch ($tab) {
      case 'all':
        $count = $this->user->getContentAllCount();
        $data = $this->user->getContentAll($offset, $per_page);
        break;
      case 'videos':
        $count = $this->user->getContentVideosCount();
        $data = $this->user->getContentVideos($offset, $per_page);
        break;
      case 'photosgallery':
        $count = $this->user->getContentPhotosgalleryCount();
        $data = $this->user->getContentPhotosgallery($offset, $per_page);
        break;
      default:
        break;
    }

    $page_count = ceil($count/$per_page);
    $this->template->page_count = $page_count;
    $this->template->page_current = $page;
    $this->template->page_offset = 4;

    $this->template->param = $tab;
    $this->template->request = 'default';
    $this->template->data = $data;
    //$this->textForTemplate("list");
    
    $this->redrawControl('featured');
  }
  
  
  
  public function renderPhotogallery($id, $page = 1)
  {
    if (!$this->user->IsLoggedIn()) { $this->redirect("Homepage:"); }
    if (!$this->user->IsBeforeExpiration()) { $this->redirect("Homepage:"); }
    
    $per_page = 18;
    $page = intval($page);
    
    if ($page < 1) { $page = 1; }
    $offset = ($page - 1) * $per_page;
    
    $photogallery = $this->user->getPhotogallery($id);
    $photos = $this->user->getPhotosByPhotogallery($id, $offset, $per_page);
    
    $page_count = ceil($photogallery->count/$per_page);
    $this->template->page_count = $page_count;
    $this->template->page_current = $page;
    $this->template->page_offset = 4;
    
    $this->template->photogallery = $photogallery;
    $this->template->photos = $photos;
    
    $this->template->seo_title = $photogallery["seo_title"];
    $this->template->seo_description = $photogallery["seo_description"];
    $this->template->seo_keywords = $photogallery["seo_keywords"];
    
    $this->template->request = 'photogallery';
    $this->template->param = $id;
    
  }
  
  
  public function renderVideo($id, $tab = 'video')
    {                
        $video = $this->user->GetVideo($id);
        $photos = $this->user->getPhotosByVideo($id, 4);
        
        if ($video["type"] != "top") {
            if (!$this->user->IsLoggedIn()) $this->redirect("Homepage:");     
            if (!$this->user->IsBeforeExpiration()) $this->redirect("Homepage:");
        }
        
        $this->template->tab = $tab;
        
        $this->template->video = $video;
        $this->template->photos = $photos;
        
        $this->template->seo_title = $video["seo_title"];
        $this->template->seo_description = $video["seo_description"];
        $this->template->seo_keywords = $video["seo_keywords"];
        
        $this->redrawControl('videodetail');
    }
  
  
}
