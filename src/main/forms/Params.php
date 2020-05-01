<?php
namespace main\forms;

use telegram\exception\TelegramException;
use main\modules\TelegramBot;
use std, gui, framework, main;

/**
 * Главная форма с настройками
 */
class Params extends AbstractForm {
    
    /**
     * @event show 
     */
    function doShow(){    
        $this->title = 'TelegramRC Bot v ' . AppModule::APP_VERSION;
    
        // Загрузка токена
        $token = Config::get('token');
        $this->edit_token->text = $token;   
         
        // Загрузка API URL
        $api_url = Config::get('api_url');
        $this->edit_api_url->text = $api_url;
        
        // Загрузка списка пользователей
        $users = Config::get('users');
        $this->list_users->items->clear();
        $this->list_users->items->addAll($users);
        
        // Отображаем, запущен бот или нет
        $state = $this->getBotState();
        $this->setStartButton($state);
        if($state == 'on'){
            $this->updateBotInfo();
        }
        
        // Подгружаем логи    
        $this->text_debug->text = Debug::getLogs();
        $this->text_debug->end();
        
        // Подгружаем настройки
        $this->checkbox_autorun->selected = Config::get('autorun');
        $this->checkbox_iconified->selected = Config::get('iconified');
        $this->checkbox_logs->selected = Config::get('save_logs');
        $this->number_restart->enabled =
        $this->checkbox_restart->selected = Config::get('restart', false);
        $this->number_restart->value = Config::get('restart_minutes', 60);
                
        // Иконки у табов
        $this->tabPane->tabs->offsetGet(0)->graphic = new UXImageView(new UXImage('res://.data/img/console.png'));
        $this->tabPane->tabs->offsetGet(1)->graphic = new UXImageView(new UXImage('res://.data/img/connection.png'));
        $this->tabPane->tabs->offsetGet(2)->graphic = new UXImageView(new UXImage('res://.data/img/users.png'));
        $this->tabPane->tabs->offsetGet(3)->graphic = new UXImageView(new UXImage('res://.data/img/bug.png'));
        $this->tabPane->tabs->offsetGet(4)->graphic = new UXImageView(new UXImage('res://.data/img/info.png'));
        
        // При сворачивании помещаем в трей 
        $this->observer('iconified')->addListener(function($old, $new){
            if($new){
                $this->free();
            }
        });
        
        // Событие при изменении числового поля с клавиатуры
        $this->number_restart->editor->observer('text')->addListener(function(){
            $this->configRestart();
        });
    }
    
    /**
     * При закрытии формы останавливаем программу.
     * Exit нужен, чтоб закрыть программу, когда активен трей.
     * @event close 
     */
    function doClose(UXWindowEvent $e){   
        if(uiConfirm('Бот будет остановлен, продолжить?')){
            app()->appModule()->shutdown();
        }
        
        $e->consume();
    }


    /**
     * Сохранение нового токена
     * @event button_save_token.action 
     */
    function saveToken(){
        $token = $this->edit_token->text;
        if(strlen($token) < 40){
            return alert('Ошибка: заполните поле token!');
        } 
        Config::set('token', $token);
        
        $api_url = $this->edit_api_url->text;
        if(!str::startsWith($api_url, 'https://')){
            return alert('Ошибка: API URL должен начинаться с https://');
        }
        Config::set('api_url', $api_url);
        
        // Если бот активен, его нужно перезапустить
        if($this->getBotState() == 'on'){
            $this->runBot('off');
            $this->showPreloader();
            waitAsync(1500, function(){
                $this->hidePreloader();
                $this->runBot('on');
            });
        }
        
        $this->getBot()->initBot($token);
        $this->getBot()->setApiURL($api_url);
    }
    
    /**
     * @return TelegramBot 
     */
    function getBot(){
        return app()->appModule()->tgBot;
    }

    /**
     * Добавление пользователя
     * @event button_add_user.action 
     */
    function addUser(){  
        $user = $this->edit_username->text;
        if(strlen($user) == 0) return alert('Ошибка: введите имя пользователя!');
        
        $users = $this->list_users->items->toArray();
        if(in_array($user, $users)) return alert('Ошибка: пользователь с таким ником уже добавлен!');
        
        $this->edit_username->text = null;
        $this->list_users->items->add($user);
        $users[] = $user;
        Config::set('users', $users);
        $this->getBot()->setUsers($users);
    }    
    
    /**
     * Удаление пользователя
     * @event button_delete_user.action 
     */
    function deleteUser(){   
        $this->button_delete_user->enabled = false;
        $selected = $this->list_users->selectedIndex;
        $this->list_users->items->removeByIndex($selected);
        Config::set('users', $this->list_users->items->toArray());
        $this->getBot()->setUsers($this->list_users->items->toArray());
    }

    /**
     * Выбор из списка пользователей
     * @event list_users.action 
     */
    function doListUsers(){ 
        $this->button_delete_user->enabled = true;
    }

    /**
     * Переключение состояния бота (вкл/выкл)
     * @event button_run.action 
     */
    function toggleBot(){  
        $state = $this->getBotState();
        $newState = ($state == 'on' ? 'off' : 'on');
        $this->runBot($newState);
    }

    /**
     * Запустить или остановить бота 
     * @param string $value "on | off"
     */
    function runBot($state){
        $this->showPreloader();
        switch($state){
            case 'on':
                if($this->updateBotInfo()){
                    $this->getBot()->startListener();
                    $this->setStartButton('on');
                } else {
                    alert('Произошла ошибка! Проверьте интернет-подключение.');
                }
                break;
            
            default:
                $this->getBot()->stopListener();
                $this->setStartButton('off');
        }
        $this->hidePreloader();
    }
    
    /**
     * Изменить текст на кнопке
     * @param string $value "on | off"
     */
    function setStartButton($state){
        switch($state){
            case 'on':
                $this->button_run->text = 'Бот активирован';
                    $this->button_run->graphic = new UXImageView(new UXImage('res://.data/img/online.png'));
                    $this->button_run->selected = true;
                break;
            
            default:    
            case 'off':
                $this->button_run->text = 'Бот деактивирован';
                $this->button_run->graphic = new UXImageView(new UXImage('res://.data/img/offline.png'));
                $this->button_run->selected = false;
        }
    }
    
    /**
     * Узнать запущен бот или нет 
     * @return string $value "on | off"
     */
    function getBotState(){
        return $this->getBot()->getStatus();
    }
    
    /**
     * Пытается обновить информацию о боте, которому сообветствует текущий token 
     */
    function updateBotInfo(): bool {
        try{
            $me = $this->getBot()->getMe();
            if($me->is_bot){
                $this->link_botnick->text = '@' . $me->username;
                $this->label_botname->text = $me->first_name;
                Debug::info('Bot token OK. Name: ' . $me->first_name . '. Nick: ' . $me->username . '.');
                return true;
            }
        } catch (\Exception $e){
            $this->link_botnick->text = '-';
            $this->label_botname->text = '-';
            Debug::error('Bot token checking error: [' . get_class($e).  '] ' . $e->getMessage());
            $this->runBot('off');
        }
        
        return false;
    }

    /**
     * @event button_clear_logs.action 
     */
    function clearLogs(){
        Debug::clearLogs();
        $this->text_debug->clear();
    }


    /**
     * @event checkbox_autorun.click 
     * @event checkbox_autorun.keyUp
     */
    function configAutorun(){   
        $value = $this->checkbox_autorun->selected;
        Config::set('autorun', $value);
    }

    /**
     * @event checkbox_iconified.click 
     * @event checkbox_iconified.keyUp 
     */
    function configIconified(){  
        $value = $this->checkbox_iconified->selected;
        Config::set('iconified', $value);
    }

    /**
     * @event checkbox_logs.click 
     * @event checkbox_logs.keyUp 
     */
    function configLogs(){    
        $value = $this->checkbox_iconified->selected;
        Debug::$saveLogs = $value;
        Config::set('save_logs', $value);
    }

    /**
     * @event link_about.action 
     */
    function doLinkAbout(){    
        browse('https://tssaltan.top/?p=1928&utm_source=program');
    }
    
    /**
     * Нажатие на ссылку бота
     * @event link_botnick.action 
     */
    function doLink(){
        $url = str_replace('@', 'https://t.me/', $this->link_botnick->text);
        if($this->getBotState() == 'on'){
            browse($url);        
        }
    }

    /**
     * @event tabPane.change 
     */
    function doTabPaneChange(UXEvent $e = null){    
        $programTime = (time() - app()->appModule()->startup) * 1000;
        $ptime = new Time($programTime, TimeZone::UTC()); 
        $this->label_uptime->text = ($ptime->day() - 1) . 'd ' . $ptime->hourOfDay() . 'h ' . $ptime->minute() . 'm';
    }

    /**
     * @event link_default_api.action 
     */
    function doLink_default_apiAction(UXEvent $e = null){    
        $this->edit_api_url->text = $e->sender->text;
    }

    /**
     * @event checkbox_restart.click 
     * @event checkbox_restart.keyPress 
     * @event number_restart.click 
     * @event number_restart.keyPress 
     */
    function configRestart($e = null)
    {    
        $minutes = intval($this->number_restart->editor->text);
        Config::set('restart', $this->checkbox_restart->selected);
        Config::set('restart_minutes', $minutes);
        
        $this->number_restart->enabled = $this->checkbox_restart->selected;
        
        if($this->checkbox_restart->selected && $minutes > 0){
        
            $this->appModule()->setRestartTime($minutes);        
            Debug::info('Enable automatic restart. (time = ' . $minutes . ' minute(s))');
        } else {
            $this->appModule()->setRestartTime(-1);
            Debug::info('Disable automatic restart (time = -1)');
        }
    }
}
