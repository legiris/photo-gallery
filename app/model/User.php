<?php

namespace App\Model;

use Nette;

class User
{
	

	/** @var Nette\Database\Context */
	private $database;

    /** @var UserManager */
    private $userManager;
    
    /** @var VideoManager */
    private $videoManager;
            
    /** @var TextManager */
    private $textManager;
    
    /** @var TariffManager */     
    private $tariffManager;
    
    /** @var OrderManager */
    private $orderManager;
    
    /** @var VerifyCodeManager */
    private $verifyCodeManager;
    
    /** @var EmailManager */
    private $emailManager;
    
    /** @var PhotoManager */
    private $photoManager;
    
    /** @var ContentManager */
    private $contentManager;
    
    private $user_id;
    private $data;
    
    
	public function __construct(Nette\Database\Context $database, $user_id)
	{
		$this->database = $database;
        
        $this->userManager = new UserManager($this->database);
        $this->videoManager = new VideoManager($this->database);        
        $this->textManager = new TextManager($this->database);
        $this->tariffManager = new TariffManager($this->database);
        $this->orderManager = new OrderManager($this->database);
        $this->verifyCodeManager = new VerifyCodeManager($this->database);
        $this->emailManager = new EmailManager($this->database);
        $this->photoManager = new PhotoManager($this->database);
        $this->contentManager = new ContentManager($this->database);
        
        $this->videoManager->SetFilter(true, false, false, true);
        
        $this->user_id = $user_id;
        
        $this->data = $this->userManager->get($this->user_id);
                
	}

    
    public function Detail()
    {
        $detail = $this->userManager->get($this->user_id);        
        $detail["tariff"] = $this->orderManager->ActualTariff($this->user_id);
        
        return $detail;
    }
    
    public function IsLoggedIn()
    {
        if ($this->user_id == 0) return false;
        return true;
    }
    
    public function IsBeforeExpiration()
    {
        if (time() < $this->data["date_expire"]) return true;
        return false;
    }
    
    public function ChangePassword($password_old, $password_new)
    {
        return $this->userManager->ChangePassword($this->user_id, $password_old, $password_new);
    }        
    
    public function ChangeEmail($email_new)
    {
        return $this->userManager->ChangeEmail($this->user_id, $email_new);
    }
        
    public function GetText($page_id)
    {
        return $this->textManager->GetText($page_id);
    }
    
    public function GetTariffs()
    {
        return $this->tariffManager->GetTariffs();
    }
    
    
    public function GetVideoTrailer()
    {
        return $this->videoManager->GetVideoTrailer();
    }
    
    public function GetVideos($offset, $limit)
    {
        return $this->videoManager->GetVideos($offset, $limit);
    }
    
    public function GetVideosCount()
    {
        return $this->videoManager->GetVideosCount();
    }
    
    public function GetVideosTopRandom()
    {
        return $this->videoManager->GetVideosTopRandom();
    }
    
    public function GetVideosTop()
    {
        return $this->videoManager->GetVideosTop();
    }
    
    public function GetVideosRandom()
    {
        return $this->videoManager->GetVideosRandom();
    }
    
    public function GetVideo($id) 
    {
        return $this->videoManager->GetVideo($id);
    }
    
    
    public function PasswordResetRequest($email)
    {                        
        $user_ids = $this->userManager->FindByEmail($email);
        
        foreach ($user_ids as $user_id) {
            $user = $this->userManager->get($user_id);
            $key = $this->verifyCodeManager->GeneratePasswordResetCode($user_id);            
            $url = "http://project.com/sign/in?do=formPasswordNew&v=$key";            
            $this->emailManager->SendLinkPasswordReset($email, $user["username"], $url);
        }        
        if (count($user_ids)) return true;
        else return false;              
    }
    
    public function PasswordResetCheck($key)
    {        
        return $this->verifyCodeManager->CheckPasswordResetCode($key);;
    }
    
    public function PasswordResetFinish($key, $password)
    {
        $valid_user_id = $this->verifyCodeManager->CheckPasswordResetCode($key);
        if ($valid_user_id) {
            $this->userManager->ChangePasswordForce($valid_user_id, $password);
            $this->verifyCodeManager->UsePasswordResetCode($key);
            return true;
        } else {
            return false;        
        }
    }
    
    public function Exists($username)
    {
        return $this->userManager->exists($username);
    }
    
    public function ZombaioUserAdd($username, $password, $email, $zombaio_pricing_id, $subscription_id)
    {        
        //if ($this->userManager->exists($username)) return false;
        $result = $this->userManager->zombaioUserAdd($username, $password, $email, $zombaio_pricing_id, $subscription_id);
        return $result;
    }
    
    public function ZombaioUserDelete($subscription_id, $username)
    {
        return $this->userManager->zombaioUserDelete($subscription_id, $username);
    }
    
    public function getVideosRecent($count)
    {
      return $this->videoManager->getVideosRecent($count);
    }
    
    public function getPhotosgalleryRecent($count)
    {
      return $this->photoManager->getPhotosgalleryRecent($count);
    }
 
    public function GetPhotogalleryByVideo($videoId) 
    {
        return $this->photoManager->getPhotogalleryByVideo($videoId);
    }
    
    
    public function getPhotosByVideo($videoId, $count = NULL)
    {
      return $this->photoManager->getPhotosByVideo($videoId, $count);
    }
    
    
    public function getContentAll($from, $count)
    {
      return $this->contentManager->getAll($from, $count);
    }
    
    public function getContentVideos($from, $count, $where = FALSE)
    {
      return $this->contentManager->getVideos($from, $count, $where);
    }
    
    public function getContentPhotosgallery($from, $count)
    {
      return $this->contentManager->getPhotosgallery($from, $count);
    }
    
    public function getContentAllCount()
    {
      return $this->contentManager->getAllCount();
    }
    
    public function getContentVideosCount()
    {
      return $this->contentManager->getVideosCount();
    }
    
    public function getContentPhotosgalleryCount()
    {
      return $this->contentManager->getPhotosgalleryCount();
    }
    

    public function getPhotosByPhotogallery($photogalleryId, $from, $count)
    {
      return $this->photoManager->getPhotosByPhotogallery($photogalleryId, $from, $count);
    }
    
    public function getPhotogallery($photogalleryId)
    {
      return $this->photoManager->getPhotogallery($photogalleryId);
    }
    
}