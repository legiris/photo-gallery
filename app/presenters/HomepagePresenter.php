<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	public function renderDefault($tab = 'all')
	{
    if (!$this->user->IsLoggedIn()) { $tab = 'videos'; }
    $this->template->video_trailer = $this->user->GetVideoTrailer();

    switch ($tab) {
      case 'all':
        $data = $this->user->getContentAll(0, 6);
        break;
      case 'videos':
        $data = $this->user->IsLoggedIn() == FALSE ? $this->user->getContentVideos(0, 6, "type='top'") : $this->user->getContentVideos(0, 6, "type != 'trailer'");
        break;
      case 'photosgallery':
        $data = $this->user->getContentPhotosgallery(0, 6);
        break;
      default:
        break;
    }
    
    $this->template->param = $tab;
    $this->template->data = $data;

    $this->textForTemplate("homepage");   
    
    $this->redrawControl("homepage");
	}

    public function renderContact()
    {
      $this->textForTemplate("contact");
    }
    
    public function renderTerms()
    {
      $this->textForTemplate("terms");
    }
}
