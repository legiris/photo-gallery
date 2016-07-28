<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    public $database;
    
    /** @var \App\Model\User */
    public $user;
    
    /** @var \App\Model\Admin */
    public $admin;
    
    
    public function startup() {
        parent::startup();
        
        \Tracy\Debugger::enable();
        
        $database = $this->context->getService("database");
        $this->database = $database;        
            
        $user_id = 0;
        $admin_id = 0;
            
        if ($this->getUser()->isLoggedIn()) {
            if ($this->getUser()->isInRole("user") || $this->getUser()->isInRole("admin")) $user_id = $this->getUser()->getId();
            if ($this->getUser()->isInRole("admin")) $admin_id = $this->getUser()->getId();
        }
                
        $this->user = new \App\Model\User($this->database, $user_id);        
        $this->admin = new \App\Model\Admin($this->database, $admin_id);
        
        $this->template->user_is_loggin = $this->user->IsLoggedIn();        
        $this->template->admin_is_loggin = $this->user->IsLoggedIn();
        
        $this->template->user_allowed_video = $this->user->IsBeforeExpiration();
                
        $userDetail = $this->user->Detail();
        $this->template->user_login = $userDetail["username"];
        
        
        
    }
    
    public function beforeRender()
    {
        $this->template->addFilter("time_duration", (function ($x) {        
            $x = intval($x);
            $seconds = $x%60;
            $minutes = ($x-$seconds)/60;
            return $minutes.":".str_pad($seconds, 2, "0", STR_PAD_LEFT);
        }));
        $this->template->addFilter("money", (function ($x) {
            $x = intval($x);
            return number_format($x/100,2,".",",");
        }));
        $this->template->addFilter("months", (function ($x) {
            if ($x==1) return "1 month";
            else return "$x months";
        }));
        $this->template->addFilter("days", (function ($x) {
            if ($x==1) return "1 day";
            else return "$x days";
        }));
        
        $this->template->header_logo_only = false;
        $this->template->footer_bottom_only = false;
    }
    
    
    public function textForTemplate($page_id)
    {
        $text = $this->user->GetText($page_id);
        
        $this->template->seo_title = $text["seo_title"];
        $this->template->seo_description = $text["seo_description"];
        $this->template->seo_keywords = $text["seo_keywords"];        
        
        $this->template->text_content = $text["text"];
        $this->template->text_content2 = $text["text2"];
        $this->template->text_content3 = $text["text3"];
        
        $homepageText = $this->user->GetText('homepage');
        $this->template->homepage_title = $homepageText['text'];
    }
    
    public function handleMessageBeMember()
    {
        $this->flashMessage("Join now to see full video.","alert");
        $this->redrawControl("flashMessages");
    }
    
    public function handleMessageBeMemberPhoto()
    {
      $this->flashMessage('Join now to see gallery.', 'alert');
      $this->redrawControl('flashMessages');
    }
    
}
