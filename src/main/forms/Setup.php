<?php
namespace main\forms;

use Exception;
use php\io\IOException;
use std, gui, framework, main;


class Setup extends AbstractForm {

    public $proxy = null;
    
    /**
     * @event button_next.action 
     */
    function do_next(){  
        $this->progressBar->progressK = $this->tabPane->selectedIndex / $this->tabPane->tabs->count;
          
        switch ($this->tabPane->selectedIndex){
            case 0:
                $this->tabPane->selectedIndex++;
                break;
                
            case 1:
                $proxy_type = $this->combobox_proxy_type->value;
                $proxy_host = $this->edit_proxy_host->text;
                $proxy_port = $this->edit_proxy_port->text;
                
                if(strlen($proxy_host) > 0 && strlen($proxy_port) > 0){      
                    $this->proxy = new Proxy($proxy_type, $proxy_host, $proxy_port);   
                    $url = URLConnection::create('https://ipinfo.io/json', $this->proxy);
                } else {
                    $url = URLConnection::create('https://ipinfo.io/json');
                }
                
                $this->button_next->enabled = false;
                $this->label_proxy_check->visible = true;
                $this->label_proxy_check->text = 'Проверка подключения ...';
                
                $thread = new Thread(function() use ($url){
                    try {
                        $connect = $url->connect();
                        $input = $url->getInputStream()->readFully();
                        
                        uiLater(function() use ($input){
                            $input = json_decode($input, true);
                            $this->label_proxy_check->text = 'Соединение установлено. Внешний IP: ' . $input['ip'];
                            Timer::after('3s', function(){                            
                                $this->button_next->enabled = true;
                                $this->tabPane->selectedIndex++;
                            });
                        });
                        
                    } catch(IOException $e){
                        uiLater(function(){
                            alert('Ошибка: не удалось установить соединение. Проверьте параметры подключения.');
                            $this->label_proxy_check->text = 'Ошибка: не удалось установить соединение.';
                            $this->button_next->enabled = true;
                        });
                    }
                });
                
                $thread->start();
              
                
            break;
            
            case 2:
                $token = $this->edit_token->text;
                if(strlen($token) < 40){
                    alert('Ошибка: заполните поле token!');
                } else {
                    $this->tabPane->selectedIndex++;
                }
                break;
                
            case 3:
                $user = $this->edit_user->text;
                if(strlen($user) < 3){
                    alert('Ошибка: заполните имя пользователя!');
                } else {
                    $this->saveParams();
                }
                break;
        }
    }

    /**
     * @event botfather_link.action 
     */
    function doBotfather(UXEvent $e = null) {
        browse('https://t.me/botfather');
    }

    /**
     * @event close 
     */
    function doClose(){    
        app()->appModule()->shutdown();
    }

    function saveParams(){
        $proxy_type = $this->combobox_proxy_type->value;
        $proxy_host = $this->edit_proxy_host->text;
        $proxy_port = $this->edit_proxy_port->text;
        $token = $this->edit_token->text;
        $user = $this->edit_user->text;
        
        $this->initBot($token);
        if(!is_null($this->proxy)){
            $this->setProxy($this->proxy);
        }
        
        $this->showPreloader('Подключение к боту ...');
        
        $thread = new Thread(function() use ($token, $user, $proxy_type, $proxy_host, $proxy_port){
            try{
                $test = $this->getMe();
                uiLater(function() use ($test, $token, $user, $proxy_type, $proxy_host, $proxy_port){
                    $this->hidePreloader();
                    if(isset($test->is_bot) && $test->is_bot){
                        alert('Связь с ботом "'. $test->first_name .'" (@'. $test->username .') установлена!');
                        
                        Config::set('token', $token);
                        Config::set('users', [$user]);
                        
                        if(!is_null($this->proxy)){
                            Config::set('proxy', [
                                'type' => $proxy_type,
                                'host' => $proxy_host, 
                                'port' => $proxy_port 
                            ]);
                        }
                        
                        $this->hide();
                        app()->appModule()->сonstruct();
                        
                    } else {
                        alert('Произошла ошибка. Возможно указан неверный token.');
                        $this->tabPane->selectedIndex = 0;
                        $this->do_next();
                    }
                });
            } catch (\Exception $e) {
                uiLater(function() use ($e){
                    alert('Произошла ошибка: ' . $e->getMessage());
                    $this->tabPane->selectedIndex = 0;
                    $this->do_next();
                });
            }
        });
        $thread->start();
        
    }
}
