<?php

namespace App\Presenters;

use Nette,
	App\Model;

class AdminPresenter extends BasePresenter
{

    public $args;    
    
    public function __construct() {
        parent::__construct();
        
        $this->args = array();
    }
    
    public function startup() {
        parent::startup();
        
        $this->template->content_page_admin = true;
        
        if (!$this->admin->IsLoggedIn() && $this->action != "login") $this->redirect("Admin:Login");                     
    }
    
    public function renderLogin()
    {
        if ($this->admin->IsLoggedIn()) $this->redirect("Admin:");     
        
        $this->template->header_logo_only = true;
    }
    
    public function renderSignIn()
    {
        
    }
    
	public function renderDefault()
	{
        
	}
    
    public function renderTariffs()
    {
        $this->template->tariffs = $this->admin->GetTariffs();
    }
    
    public function renderTexts()
    {
        $this->template->texts = $this->admin->GetTexts();        
    }
    
    public function renderText($page_id)
    {
        $this->template->textTitle1 = "";
        $this->template->textTitle2 = "";
        $this->template->textTitle3 = "";
        
        switch ($page_id) {
            case "homepage":
                $this->template->textTitle1 = "Nadpis";
                $this->template->textTitle2 = "Text";
                break;
            case "login":
                $this->template->textTitle1 = "Text";
                $this->template->textTitle2 = "Text (dole)";
                break;
            case "signup":
                $this->template->textTitle1 = "Text";
                break;
            case "list":
                break;
            case "order":
                $this->template->textTitle1 = "Text 1";
                $this->template->textTitle2 = "Text 2";
                $this->template->textTitle3 = "Text 3";
                break;
            case "contact":
                $this->template->textTitle1 = "Text";
                break;
            case "terms":
                $this->template->textTitle1 = "Text";
                break;            
        }
        /*
        if (in_array($page_id, array("homepage","login","signup","contact","terms"))) $this->template->textTitle1 = "Text";
        
        $this->template->showText1 = false;
        $this->template->showText2 = false;
        $this->template->showText3 = false;
        if (in_array($page_id, array("homepage","login","signup","contact","terms"))) $this->template->showText1 = true;
        if (in_array($page_id, array("login"))) $this->template->showText2 = true;
        */
        
        $this->template->page_id = $page_id;
        $this->template->text = $this->admin->GetText($page_id);
    }
    
    
    public function VideosToTemplate()
    {
        $tab = "all";
        $page = 1;
        if (isset($this->args["tab"])) $tab = $this->args["tab"];
        if (isset($this->args["page"])) $page = $this->args["page"];
        
        switch ($tab) {
            case "all":
                $page_count = $this->admin->GetVideosAllPageCount();
                $videos = $this->admin->GetVideosAll($page-1);
                break;
            case "trailer":
                $page_count = $this->admin->GetVideosTrailerPageCount();
                $videos = $this->admin->GetVideosTrailer($page-1);
                break;
            case "top":
                $page_count = $this->admin->GetVideosTopPageCount();
                $videos = $this->admin->GetVideosTop($page-1);                
                break;
            default:
                $page_count = 0;
                $videos = array();                
        }
        
        
        $this->template->tab = $tab;
        $this->template->page_selected = $page;
        $this->template->page_count = $page_count;
        $this->template->videos = $videos;
        
        $this->redrawControl("videos");
    }
    
    public function renderVideos($tab = "all", $page=1)
    {
        $this->args["tab"] = $tab;
        $this->args["page"] = $page;
        
        $this->VideosToTemplate();        
    }
    
    public function renderVideo($video_id = "")
    {
        
        $this->template->trailer_exists = $this->admin->TrailerExists();
        
        $this->template->video = $this->admin->GetVideo($video_id);
        
        if ($video_id) {
            $this->template->video_editing = true;
            $this->template->video_creating = false;
        } else {
            $this->template->video_editing = false;
            $this->template->video_creating = true;
        }
        
    }
    
    public function actionLogout()
    {
        $this->getUser()->logout();
        $this->redirect("Admin:login");
    }
    
    public function handleVideoDelete($id)
    {
        $this->admin->DeleteVideo($id);
    }
    
    public function handleVideoSequenceUp($id)
    {
        $this->admin->SetVideoSequenceUp($id);
        $this->VideosToTemplate();
    }
    
    public function handleVideoSequenceDown($id)
    {
        $this->admin->SetVideoSequenceDown($id);
        $this->VideosToTemplate();
    }
    
    public function handleVideoSequenceUp2($id)
    {
        $this->admin->SetVideoSequenceUp2($id);
    }
    
    public function handleVideoSequenceDown2($id)
    {
        $this->admin->SetVideoSequenceDown2($id);
    }    
    
    
    public function handleVideoInsertCheck($code="")
    {
        $result = $this->admin->CheckVideoInsert($code);
        $this->payload->result = $result;
        $this->sendPayload();
    }
    
    public function handleBrowseDirectory($dir, $type)
    {                      
        if (!$this->admin->IsLoggedIn()) return;
        
        $root = DIR_ROOT;
        $data = "";
        
        if(file_exists($root.$dir)) {
            $files = scandir($root.$dir);
            natcasesort($files);
            if(count($files)>2) { /* The 2 accounts for . and .. */
                $data .= "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
                // All dirs
                foreach($files as $file) {
                    if(file_exists($root.$dir.$file) && $file != '.' && $file != '..' && is_dir($root.$dir.$file)) {
                        $data .= "<li class=\"directory collapsed\">".($type == 'photo' ? "<input type=\"checkbox\">" : '')."<a href=\"#\" rel=\"".htmlentities($dir.$file)."/\">".htmlentities($file)."</a></li>";
                    }
                }
                // All files
                foreach( $files as $file ) {
                    if( file_exists($root.$dir.$file) && $file != '.' && $file != '..' && !is_dir($root.$dir.$file) ) {
                        $ext = preg_replace('/^.*\./', '', $file);
                        $data .= "<li class=\"file ext_$ext\">".($type == 'photo' ? "<input type=\"checkbox\">" : '')."<a href=\"#\" rel=\"".htmlentities($dir.$file)."\">".htmlentities($file)."</a></li>";
                    }
                }
                $data .= "</ul>";
            }
        }
        
    $response = new \Nette\Application\Responses\TextResponse($data);				
		$this->getHttpResponse()->setHeader("Content-Type", "text/html");	
		$this->sendResponse($response);    
    }

    public function createComponentAdminSignInForm($name)
    {
        return new \App\Forms\AdminSignInForm($this, $name);
    }
    
    public function createComponentAdminTariffsForm($name)
    {
        $form = new \App\Forms\AdminTariffsForm($this, $name);
        if (isset($this->template->tariffs)) {
            $tariffs = $this->template->tariffs;
            $g = $tariffs["green"];
            $b = $tariffs["blue"];
            $r = $tariffs["red"];

            $form->setDefaults(array(
                "name1" => $g["name"],
                "period1" => $g["period"],
                "price1" => $g["price"]/100,
                "zombaio1" => $g["zombaio_pricing_id"],

                "name2" => $b["name"],
                "period2" => $b["period"],
                "price2" => $b["price"]/100,
                "zombaio2" => $b["zombaio_pricing_id"],

                "name3" => $r["name"],
                "period3" => $r["period"],
                "price3" => $r["price"]/100,
                "zombaio3" => $r["zombaio_pricing_id"],
            ));
        }
        return $form;
    }
    
    public function createComponentAdminTextForm($name)
    {
        $form = new \App\Forms\AdminTextForm($this, $name);
        
        if (isset($this->template->text)) {
            $text = $this->template->text;
            $form->setDefaults(array(
                "page_id" => $text["page_id"],
                "text" => $text["text"],
                "text2" => $text["text2"],
                "text3" => $text["text3"],
                "seo_title" => $text["seo_title"],
                "seo_description" => $text["seo_description"],
                "seo_keywords" => $text["seo_keywords"]
            ));
        }
        
        return $form;
    }
    
    public function createComponentAdminVideoForm($name)
    {
        $form = new \App\Forms\AdminVideoForm($this, $name);
        
        if (isset($this->template->video)) {
            $video = $this->template->video;
            $form->setDefaults(array(
                "video_id" => $video["id"],
                "name" => $video["name"],
                "description" => $video["description"],
                "seo_title" => $video["seo_title"],
                "seo_description" => $video["seo_description"],
                "seo_keywords" => $video["seo_keywords"],
                "is_main_trailer" => $video["type"]=="trailer",
                "is_active" => $video["active"],
                "filename_video" => $video["video_full_4k"],
                "filename_trailer" => $video["video_trailer_4k"]
            ));
        } else {
            $form->setDefaults(array(
                "is_main_trailer" => false
            ));
        }
        
        return $form;
    }
    
    public function createComponentAdminEmailForm($name)
    {
        $form = new \App\Forms\AdminEmailForm($this, $name);
        
        $setting = $this->admin->GetEmailSetting();
        $form->setDefaults(array(
            "sender" => $setting["sender"],
            "sender_name" => $setting["sender_name"],
            "response" => $setting["response"],
            "smtp" => $setting["smtp"],
            "smtp_host" => $setting["smtp_host"],
            "smtp_protocol" => $setting["smtp_protocol"],
            "smtp_secure" => $setting["smtp_secure"],
            "smtp_username" => $setting["smtp_username"],
            "smtp_password" => $setting["smtp_password"],
            "system_email" => $setting["system_email"]
        ));
        
        return $form;
    }
    
    /***************** photos gallery *****************/
    
    public function renderPhotosgallery($tab = 'all', $page = 1)
    {
      $this->args["tab"] = $tab;
      $this->args["page"] = $page;

      $this->photosgalleryToTemplate();
    }
    
    public function photosgalleryToTemplate()
    {
      $tab = "all";
      $page = 1;
      
      if (isset($this->args["tab"])) $tab = $this->args["tab"];
      if (isset($this->args["page"])) $page = $this->args["page"];
      
      $selectedPage = $page;
      $countItemsPerPage = 10;

      switch ($tab) {
        case "all":
          $countItems = $this->admin->getPhotosgalleryAllCount();
          $photosgallery = $this->admin->getPhotosgalleryAll($selectedPage * $countItemsPerPage - $countItemsPerPage, $countItemsPerPage);
          break;
        case "with-video":
          $countItems = $this->admin->getPhotosgalleryWithVideoCount();
          $photosgallery = $this->admin->getPhotosgalleryWithVideo($selectedPage * $countItemsPerPage - $countItemsPerPage, $countItemsPerPage);
          break;
        case "without-video":
          $countItems = $this->admin->getPhotosgalleryWithoutVideoCount();
          $photosgallery = $this->admin->getPhotosgalleryWithoutVideo($selectedPage * $countItemsPerPage - $countItemsPerPage, $countItemsPerPage);
          break;
        default:
          $countItemsPerPage = 0;
          $photosgallery = array();                
      }
      
      $this->template->tab = $tab;
      $this->template->page_selected = $page;
      $this->template->photosgallery = $photosgallery;

      $this->template->selectedPage = $selectedPage;
      $this->template->countItems = $countItems;
      $this->template->countItemsPerPage = 10;
      
      $this->redrawControl("photosgallery");
    }
    
    
    public function renderPhotogallery($photogallery_id = "")
    {
      $this->template->photogallery = $this->admin->getPhotogallery($photogallery_id);
      
      if ($photogallery_id) {
          $this->template->photogallery_editing = true;
          $this->template->photogallery_creating = false;
          $this->template->video = $this->admin->getVideoByPhotogallery($photogallery_id);
      } else {
          $this->template->photogallery_editing = false;
          $this->template->photogallery_creating = true;
      }
    }
    

    
    public function createComponentAdminPhotogalleryForm($name)
    {
      $form = new \App\Forms\AdminPhotogalleryForm($this, $name);

      if (isset($this->template->photogallery)) {
        $photogallery = $this->template->photogallery;
        $form->setDefaults(array(
          'photogallery_id' => $photogallery['id'],
          //'video_id'        => $photogallery['video_id'],
          'name'            => $photogallery['name'],
          'seo_title'       => $photogallery['seo_title'],
          'seo_description' => $photogallery['seo_description'],
          'seo_keywords'    => $photogallery['seo_keywords'],
          'is_active'       => $photogallery['active'],
        ));
      } 

      return $form;
    }
    
    public function handlePhotogalleryDelete($id)
    {
      $this->admin->DeletePhotogallery($id);
    }
    
    
    
    /*********** photos *****************/
    public function renderPhotos($photogallery_id = '', $page = 1)
    {
      $this->args['photogallery_id'] = $photogallery_id;
      $this->args["page"] = $page;

      $this->photosToTemplate();
    }
 
    
    public function photosToTemplate()
    {
      $page = 1;
      $countItemsPerPage = 12;
      
      if (isset($this->args["photogallery_id"])) { $photogallery_id = $this->args['photogallery_id']; }
      if (isset($this->args["page"])) { $page = $this->args["page"]; }

      $countItems = $this->admin->getPhotosgalleryCount($photogallery_id);
        
      $photos = $this->admin->getPhotos($photogallery_id, $page * $countItemsPerPage - $countItemsPerPage, $countItemsPerPage);
      $this->template->photos = $photos;
      
      $this->template->photogalleryId = $photogallery_id;
      $this->template->selectedPage = $page;
      $this->template->countItems = $countItems;
      $this->template->countItemsPerPage = $countItemsPerPage;
      
      $this->redrawControl("photos");
    }
    
    public function handlePhotoDelete($id)
    {
      $this->admin->deletePhoto($id);
    }
    
    public function handlePhotoTop($id)
    {
      $this->admin->topPhoto($id);
    }
    
    
}
