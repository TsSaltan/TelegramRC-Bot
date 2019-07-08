<?php
namespace main\forms;

use telegram\exception\TelegramException;
use main\modules\TelegramBot;
use std, gui, framework, main;

/**
 * Главная форма с настройками
 * 
 * !!! Осторожно обращайтесь со спойлерами в визуальном редакторе, одно неловкое движениее и вся разметка может полететь к чертям !!!
 */
class Params extends AbstractForm {
    
    /**
     * Отображение формы
     * @event show 
     */
    function doShow(){    
        $this->title = 'TelegramRC Bot v ' . AppModule::APP_VERSION;
    
        // Загрузка токена
        $token = Config::get('token');
        $this->edit_token->text = $token;
        
        // Загрузка списка пользователей
        $users = Config::get('users');
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
        $this->spoiler_debug->minHeight = 300; // По-другому этот дибильный спойлер не меняет размеры
        
        // Подгружаем настройки
        $this->checkbox_tray->selected = Config::get('use_tray');
        $this->checkbox_autorun->selected = Config::get('autorun');
        $this->checkbox_iconified->selected = Config::get('iconified');
    }
    
    /**
     * Если при закрытии формы бот не запущен, останавливаем программу.
     * Exit нужен, чтоб закрыть программу, когда активен трей.
     * @event close 
     */
    function doClose(){    
        if($this->getBotState() == 'off'){
            exit(0);
        }
    }


    /**
     * Сохранение нового токена
     * @event button_save_token.action 
     */
    function saveToken(){
        $token = $this->edit_token->text;
        Config::set('token', $token);
        
        // Если бот активен, его нужно перезапустить
        if($this->getBotState() == 'on'){
            $this->runBot('off');
            waitAsync(1000, function(){
                $this->runBot('on');
            });
        }
    }

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
     * Запуск или остановка бота
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
                    alert('Произошла ошибка! Проверьте, корректный ли token и есть ли интернет-подключение.');
                    // $this->runBot('off');
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
                $this->label_botname->text = 
                $this->link_botnick->text = 'N/A';
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
     * @event checkbox_tray.click 
     */
    function configTray(){    
        $value = $this->checkbox_tray->selected;
        Config::set('use_tray', $value);
        
        app()->appModule()->systemTray->visible = $value;
    }

    /**
     * @event checkbox_autorun.click 
     */
    function configAutorun(){    
        $value = $this->checkbox_autorun->selected;
        Config::set('autorun', $value);
    }

    /**
     * @event checkbox_iconified.click 
     */
    function configIconified(){    
        $value = $this->checkbox_iconified->selected;
        Config::set('iconified', $value);
    }
}
