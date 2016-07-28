<?php

namespace App\Forms;

use Nette\Application\UI\Form;

class AdminPhotogalleryForm extends Form {
	
  private $parent;
  
    
  public function __construct(\App\Presenters\BasePresenter $parent, $name) 
	{
    parent::__construct($parent, $name);
    $this->parent = $parent;

    $videos = $this->parent->admin->getVideoFetchPairs();
    
    $this->addHidden("photogallery_id");
    $this->addHidden("stay");
    $this->addText("name");
    $this->addSelect('video_id', '', $videos)->setPrompt('- vyberte -');
    $this->addText("seo_title");
    $this->addTextArea("seo_description");
    $this->addText("seo_keywords");
    $this->addCheckbox("is_active");
    $this->addText('photos_filepath');
		$this->addSubmit("send");
    
    $this->onSuccess[] = array($this, 'success');
  }

  public function success(\Nette\Application\UI\Form $form) 
	{
    $values = $form->getValues();
    $photogallery_id = $values->photogallery_id;

    if (strlen($values->name) < 3) $this->addError("Název fotogalerie je příliš krátký!");
    if (strlen($values->seo_title) < 3) $this->addError("SEO titulek je příliš krátký!");
    if (strlen($values->seo_description) < 3) $this->addError("SEO popis je příliš krátký!");
    if (strlen($values->seo_keywords) < 3) $this->addError("SEO klíčová slova jsou příliš krátká!");
    $photogallery_id == '' && !$values->photos_filepath ? $this->addError ('Nebyla zadána cesta pro nahrání fotografií') : '';
  
    if ($this->hasErrors()) {
      foreach ($this->errors as $e) {
        $this->parent->flashMessage($e,"alert");                
      }
      $this->parent->redrawControl("flashMessages");
      return;
    }
      
    $admin = $this->parent->admin;
    
    if ($photogallery_id) {
      $admin->setPhotogallery(
        $values->photogallery_id,
        $values->video_id,
        $values->name,
        $values->seo_description,
        $values->seo_title,
        $values->seo_keywords,
        $values->is_active,
        $values->photos_filepath
      );
      
      $this->parent->flashMessage("Fotogalerie úspěšně uložena", "success");
      
    } else {
      $photogallery_id = $admin->createPhotogalery(
        $values->name,
        $values->video_id,
        $values->seo_title,
        $values->seo_description,
        $values->seo_keywords,
        $values->photos_filepath
      );
      
      if (!$photogallery_id) $this->parent->flashMessage("Chyba při vytváření fotogalerie!", "error");
      else $this->parent->flashMessage("Fotogalerie úspěšně vytvořena", "success");

      $this->parent->redrawControl("flashMessages");
    }
        
    if ($photogallery_id) {
      if ($values->stay) $this->parent->redirect("Admin:photogallery", array("photogallery_id" => $photogallery_id));
      else $this->parent->redirect("Admin:photosgallery");
    }
  }
  
}
