<?php
namespace main\forms;

use std, gui, framework, main;


class NotifyForm extends AbstractForm
{

    /**
     * @event show 
     */
    function doShow(UXWindowEvent $e = null)
    {    
        $padding = 5;
        $screen = UXScreen::getPrimary();
        $this->x = $screen->visualBounds['width'] - $this->width - $padding;
        $this->y = $screen->visualBounds['height'] - $this->height - $padding;
        
        $this->label->mouseTransparent = true;
        $this->label_title->mouseTransparent = true;
        $this->cursor = "HAND";
        
        $this->opacity = 0;
        Animation::fadeIn($this, 900);
        
        Timer::setTimeout(function(){
            uiLater(function(){
            Animation::fadeOut($this, 900);
            });
        }, 5000);
        
        Timer::setTimeout(function(){
            uiLater(function(){
                $this->hide();
            });
        }, 6000);
    }

    /**
     * @event click 
     */
    function doClick(){    
        $this->hide();
        $this->form('Params')->show();
    }

    /**
     * @event hide 
     */
    function doHide(){    
        $this->free();
    }
    
    public function setText(string $text){
        $this->label->text = $text;
    }
    
    public function setTitleText(string $text){
        $this->label_title->text = $text;
    }
}
