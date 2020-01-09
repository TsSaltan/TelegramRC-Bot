<?php
namespace main\forms;

use std, gui, framework, main;

/**
 * Показывает уведомление пользователю с таймером 
 */
class ConfirmTimeout extends AbstractForm {
    public $sec;
    public $func;
    public $cancel;
    public $ok;
    
    public function setText($title, $description){
        $this->label_title->text = $title;
        $this->label->text = $description;
    }    
        
    public function setButtons($ok, $cancel){
        $this->ok = $this->button_ok->text = $ok;
        $this->button_cancel->text = $cancel;
    }
    
    public function start(int $sec, callable $func, ?callable $cancel = null){
        $this->sec = $sec;
        $this->func = $func;
        $this->cancel = $cancel;
        $this->waiter();
        $this->showAndWait(); 
    }
    
    function waiter(){
        if($this->sec > 0){
            $this->button_ok->text = $this->ok . ' (' . $this->sec . ')';
            waitAsync(1000, function(){
                $this->sec--;
                $this->waiter();
            });
        } else {
            $this->button_ok->text = $this->ok;
            $this->okAction();
        }
    }
    
    /**
     * @event button_ok.action 
     */
    function okAction(){ 
        if(is_callable($this->func)) call_user_func($this->func);
        $this->func = null;
        $this->sec = 0;
        $this->free();
    }

    /**
     * @event button_cancel.action 
     */
    function cancelAction(){   
        if(is_callable($this->cancel)) call_user_func($this->cancel); 
        $this->func = null;
        $this->sec = 0;
        $this->free();
    }

    /**
     * @event close 
     */
    function doClose(){    
        $this->cancelAction();
    }
}
