<?php
namespace main\modules;

use Exception;
use telegram\tools\TUpdateListener;
use telegram\TelegramBotApi;
use std, gui, framework, main;

/**
 * Собственно здесь происходит "общение" с API Telegram
 */
class TelegramBot extends AbstractModule {
    /**
     * @var TelegramBotApi 
     */
    private $api;
    
    /**
     * Разрешённые пользователи
     * @var array 
     */
    private $users = [];
    
    /**
     * Long-poll
     * @var TUpdateListener 
     */
    private $listener;
    
    /**
     * Каждому пользователю будет соответствовать экземпляр класса команд
     * [chat_id => Commands]
     * @var array 
     */
    public $commands = [];
    
    /**
     * Числовая метка последнего события
     * Чтоб не обрабатывать события дважды
     */
    public $last_update = 0;
    
    /**
     * Состояние, запущен бот или нет
     * on | off
     * @var string
     */
    public $status = 'off';
    
    /**
     * @var callable 
     */
    public $errorCallback;
    
    public function initBot($token){
        $this->api = new TelegramBotApi($token);
    }
    
    public function setUsers(array $users){
        $this->users = array_map(function($i){ return strtolower($i); }, $users);
    }
    
    public function getStatus(){
        return $this->status;
    } 
       
    public function getMe(){
        try {
            return $this->api->getMe()->query();
        } catch (\Exception $e){
            if(is_callable($this->errorCallback)) call_user_func($this->errorCallback, $e);
            throw $e;
        }
    }
    
    public function setErrorCallback(callable $func){
        $this->errorCallback = $func;
    }
    
    public function startListener(){
        $thread = new Thread(function(){
            try{
                $this->listener = new TUpdateListener($this->api);
                $this->listener->setAsync(false); 
                $this->listener->setThreadsCount(4); 
                $this->listener->addListener([$this, 'onUpdate']);
                                
                $this->status = 'on';
                Debug::info('Long-poll activated');
                
                uiLater(function(){
                    $notify = new UXTrayNotification;
                    $notify->title = APP;
                });
                
                $this->listener->start();
            } catch (\Exception $e){
                Debug::error('Long-poll listener error: ' . '['. get_class($e) .'] ' . $e->getMessage($e));
                uiLater(function() use ($e){
                    if(is_callable($this->errorCallback)) call_user_func($this->errorCallback, $e);
                });
                $this->stopListener();
            }
        });
        
        $thread->start();
    }
    
    public function stopListener(){
        if(is_object($this->listener)){
            try{
                $this->listener->stop();
            } catch (\Exception $e){
            }
        }
        
        $this->status = 'off';
        Debug::warning('Long-poll deactivated');
    }
    
    /**
     * Обработка событий от long-poll 
     */
    public function onUpdate($update){
        try{ 
            // Сравниваем числовую метку события
            if($update->update_id > $this->last_update){
                $this->last_update = $update->update_id;
                $chat_id = $update->message->chat->id;
                $username = $update->message->from->username;
                $text = $update->message->text;
                Debug::info('[INPUT] ' . $update->message->from->username . ': ' . $text);
                
                // Проверка, есть ли такой пользователь в списке разрешённых
                if($this->checkUser($username)){
                
                    // Если ранее пользователь не обращался к боту, создадим ему экземпляр Commands
                    if(!isset($this->commands[$chat_id])){
                        $this->commands[$chat_id] = new Commands($chat_id, $username, $this);
                    }
                    $commands = $this->commands[$chat_id];
                    
                    $answer = $commands->undefinedMsg(($cmd['command'] ?? $text));
                    $cmd = $this->parseCommand($text);
                    
                    // Если удалось распасрсить команду
                    if($cmd !== false){
                        if(method_exists($commands, '__' . $cmd['command'])){
                            try{                        
                                $answer = call_user_func_array([$commands, '__' . $cmd['command']], $cmd['args']);
                            } catch (\Exception $e){
                                Debug::error('Command error: ' . $e->getMessage());
                                $answer = $commands->errorMsg($e->getMessage());
                            }
                        }
                    }
                } else {
                    $answer = (new Commands($chat_id, $username, $this))->deniedMsg();
                }
                
                $this->sendAnswer($chat_id, $answer);
            }
        } catch (\Exception $e){
            Debug::error('[OnUpdate] ' . get_class($e). ': ' . $e->getMessage());
        }
    }
    
    /**
     * Проверка пользователя
     * Лучше перевести всё в нижний регистр 
     */
    public function checkUser($username): bool {
        return in_array(strtolower($username), $this->users);
    }
    
    /**
     * Отправка ответа 
     */
    public function sendAnswer($chat_id, $data){
        if(isset($data['text'])){
           $this->api->sendMessage()->chat_id($chat_id)->text($data['text'])->query();
        }
        
        if(isset($data['photo'])){
            $this->api->sendPhoto()->chat_id($chat_id)->photo(new File($data['photo']))->query();
        }
        
        if(isset($data['doc'])){
            $this->api->sendDocument()->chat_id($chat_id)->document(new File($data['doc']))->query();
        }
   
    }
    
    public function parseCommand($input){
        $reg = '"([^"]+)"|\s*([^"\s]+)';
        $r = Regex::of($reg, Regex::CASE_INSENSITIVE | Regex::UNICODE_CASE, $input);
        $data = array_map(function($e){
            return !is_null($e[2]) ? $e[2] : (!is_null($e[1]) ? $e[1] : $e[0]);         
        }, $r->all() );
        
        $cmd = str_replace(['/','-'], ['', '_'], $data[0]);
        $args = [];
        if(sizeof($data) > 1){
            unset($data[0]);  
            $args = array_values($data);      
        }
        
        if(sizeof($args) == 0 && strpos($cmd, '__') !== false){
            $parse = explode('__', $cmd);
            $cmd = $parse[0];
            unset($parse[0]);
            $args = array_values($parse);
        }
        
        return [
            'command' => $cmd,
            'args' => $args
        ];
    }
}