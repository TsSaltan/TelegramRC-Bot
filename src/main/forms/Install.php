<?php
namespace main\forms;

use windows;
use Exception;
use std, gui, framework, main;

/**
 * Форма при первом запуске проргаммы
 * Установка, настройка токена и т.д. 
 */
class Install extends AbstractForm
{
    /**
     * ID последней панели (начиная с 0)
     * 0 - приветствие
     * 1 - пользовательское соглашение
     * 2 - получение токена
     * 3 - ввод имени пользователя
     */
    const PANELS_NUM = 3;

    /**
     * @event show 
     */
    function doShow(){  
        $this->terms_of_use_text->text = 'Пользовательское соглашение к программе "TelegramRC Bot" v ' . AppModule::APP_VERSION . ".\n" . $this->terms_of_use_text->text;
        
        // Сначала все панели скрываем
        for($i = 0; $i <= self::PANELS_NUM; ++$i){
            $this->{"panel_$i"}->opacity = 0;
            $this->{"panel_$i"}->visible = false;
            
            // Кнопка, ведущая на следующую панель
            if(isset($this->{"button_to_$i"})){
                $this->{"button_to_$i"}->on('click', function() use ($i){
                    $this->showPanel($i);
                });
            }
        }
        
        $this->showPanel();       
    }
    
    /**
     * Показать панель и скрыть предыдущие 
     */
    function showPanel($n = 0){
        $this->showPreloader();
        
        // Скрываем все панели
        for($i = 0; $i <= self::PANELS_NUM; ++$i){
            $this->{"panel_$i"}->visible = false;
            $this->{"panel_$i"}->opacity = 0;
        }

        // Показываем панель с нужным нам ID
        $this->{"panel_$n"}->visible = true;
        Animation::fadeIn($this->{"panel_$n"}, 800, function(){
            $this->hidePreloader();
        });
    }
    
    /**
     * @event botfather_link.action 
     */
    function doBotFatherLink(){    
        browse('https://t.me/botfather');
    }

    /**
     * Последний шаг установки
     * @event button_finish.action 
     */
    function doFinish(){    
        $token = $this->edit_token->text;
        $user = $this->edit_user->text;
        
        if(strlen($token) < 40 || strlen($user) < 3){
            alert('Ошибка: заполните поля token и имя пользователя!');
            return $this->showPanel(2);
        }
        
        $this->initBot($token);
        $this->showPreloader();
        
        $thread = new Thread(function() use ($token, $user){
            try{
                $test = $this->getMe();
                uiLater(function() use ($test, $token, $user){
                    $this->hidePreloader();
                    if(isset($test->is_bot) && $test->is_bot){
                        alert('Связь с ботом "'. $test->first_name .'" (@'. $test->username .') установлена!');
                        Config::set('token', $token);
                        Config::set('users', [$user]);
                        app()->appModule()->сonstruct();
                    } else {
                        alert('Произошла ошибка. Возможно указан неверный token.');
                        $this->showPanel(2);
                    }
                });
            } catch (\Exception $e) {
                uiLater(function() use ($e){
                    alert('Произошла ошибка: ' . $e->getMessage());
                    return $this->showPanel(2);
                });
            }
        });
        $thread->start();
    }
}
