<?php
namespace main\modules;

use telegram\exception\TelegramException;
use Error;
use Throwable;
use Exception;
use telegram\tools\TUpdateListener;
use telegram\TelegramBotApi;
use std, gui, framework, main;

define("SMILE_DISC", "💾");
define("SMILE_FILE", "📄");
define("SMILE_FOLDER", "🗂");
define("SMILE_PICTURE", "🖼");
define("SMILE_AUDIO", "🎼");
define("SMILE_VIDEO", "🎬");
define("SMILE_NETWORK", "🌐");
define("SMILE_BACK", "🔙");
define("SMILE_CLOCK", "🕙");
define("SMILE_BOT", "🤖");
define("SMILE_HOME", "🏠");
define("SMILE_USER", "🤵");

define("SMILE_UP", "🆙");
define("SMILE_SYMBOL_UP", "🔼");
define("SMILE_SYMBOL_DOWN", "🔽");
define("SMILE_ARROW_UP", "⤴️");
define("SMILE_ARROW_REFRESH", "🔄");
define("SMILE_ARROW_DOWN", "⬇️");
define("SMILE_ARROW_LEFT", "⬅️");
define("SMILE_ARROW_RIGHT", "➡️");
define("SMILE_ARROW_UP_DIRECT", "⬆️");

define("SMILE_DOT_RED", "🛑");
define("SMILE_DIAMOND_BLUE", "🔹");
define("SMILE_DIAMOND_ORANGE", "🔸");

define("SMILE_CLOCK", "🕓");
define("SMILE_TRASH", "🗑");
define("SMILE_PRINT", "🖨");
define("SMILE_DOWNLOAD", "🔰");
define("SMILE_PC", "💻");
define("SMILE_DISPLAY", "🖥");
define("SMILE_KEYBOARD", "⌨️");
define("SMILE_HELP", "🆘");
define("SMILE_CAMERA", "📷");

define("SMILE_MEDIA", "🎛");
define("SMILE_MEDIA_PREV", "⏪");
define("SMILE_MEDIA_STOP", "⏹");
define("SMILE_MEDIA_PLAY", "⏯");
define("SMILE_MEDIA_NEXT", "⏩");

define("SMILE_BRIGHT_100", "🔆");
define("SMILE_BRIGHT_50", "🔅");

define("SMILE_BATTERY", "🔋");
define("SMILE_TEMPERATURE", "🌡");

define("SMILE_SOUND_0", "🔇");
define("SMILE_SOUND_25", "🔈");
define("SMILE_SOUND_50", "🔉");
define("SMILE_SOUND_100", "🔊");


/**
 * Собственно здесь происходит "общение" с API Telegram
 */
class TelegramBot extends AbstractModule {

    const MAX_MESSAGE_LENGTH = 4096;
    
    const MAX_CALLBACK_DATA = 64;

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
      
    /**
     * @var callable 
     */
    public $startCallback;    
    
    /**
     * @var callable 
     */
    public $stopCallback;
    
 
    public function initBot($token){
        $this->api = new TelegramBotApi($token);
    }  
    
    public function setApiURL(string $url){
        $this->api->setApiURL($url);
    } 
    
    public function setProxy($proxy){
        $this->api->setProxy($proxy);
    }
    
    public function setUsers(array $users){
        // migrate to v 4.x
        if(isset($users[0]) && is_string($users[0])){        
            $users = array_map(function($i){ return ['name' => strtolower($i), 'id' => -1]; }, $users);    
            Config::set('users', $users);
        }
        
        $this->users = $users;
    }
        
    public function setUserId(string $user, int $id){
        $name = strtolower($user);
        foreach ($this->users as $k => $u){
            if(strtolower($u['name']) == $name){
                if($u['id'] < 0){
                    $this->users[$k]['id'] = $id;
                    Config::set('users', $this->users);
                }
                break;
            }
        }
    }
        
    public function getUsers(bool $mergeSub = true): array {
        if($mergeSub){
            return array_map(function($i){ return $i['name'] . ($i['id'] > 0 ? ' (id: '.$i['id'].')' : ''); }, $this->users);
        }
        else return $this->users;
    }   
    
    public function addUser(string $user, int $id = -1){
        $this->users[] = ['name' => $user, 'id' => $id];
        Config::set('users', $this->users);
    }
        
    public function deleteUser(string $user){
        $name = strtolower($user);
        foreach ($this->users as $k => $u){
            if(strtolower($u['name']) == $name){
                unset($this->users[$k]);
            }
        }
        
        $this->users = array_values($this->users);
        Config::set('users', $this->users);
    }        
    
    public function deleteUserByIndex(int $index){        
        unset($this->users[$index]);
        $this->users = array_values($this->users);
        Config::set('users', $this->users);
    }
            
    public function isUserExists(string $user): bool{
        $name = strtolower($user);
        foreach ($this->users as $k => $u){
            if(strtolower($u['name']) == $name){
                return true;
            }
        }
        
        return false;
    }
     
    /**
     * Проверка пользователя
     * Лучше перевести всё в нижний регистр 
     */
    public function checkUser($username): bool {
        $username = strtolower($username);
        foreach ($this->users as $k => $user){
            if(strtolower($user['name']) == $username){
                return true;
            }
        }
        
        return false;
    }
    
    public function getStatus(){
        return $this->status;
    } 
    
    public function getApi(): TelegramBotApi {
        return $this->api;
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
    
    public function setStartCallback(callable $func){
        $this->startCallback = $func;
    }    
    
    public function setStopCallback(callable $func){
        $this->stopCallback = $func;
    }
    
    public function startListener(){
        $thread = new Thread(function(){
            try{
                if(is_callable($this->startCallback)) call_user_func($this->startCallback);
                
                $this->listener = new TUpdateListener($this->api);
                $this->listener->setAsync(false); 
                $this->listener->setThreadsCount(4); 
                $this->listener->addListener([$this, 'onUpdate']);
                                
                $this->status = 'on';
                Debug::info('Long-poll activated');
                 
                $this->listener->start();
            } catch (\Exception $e){
                Debug::error('Long-poll listener error: ' . '['. get_class($e) .'] ' . $e->getMessage($e));
                
                if($e instanceof TelegramException){
                    $e = $e->getPrevious();
                    Debug::error('['. get_class($e) .'] ' . $e->getMessage($e));
                }
                
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
        if(is_callable($this->stopCallback)) call_user_func($this->stopCallback);
        Debug::warning('Long-poll deactivated');
    }
    
    /**
     * Обработка событий от long-poll 
     */
    public function onUpdate($update){
        //Debug::info('[Update] ' . json_encode($update, JSON_PRETTY_PRINT));
        try{ 
            $last_doc = null;
            $callback_id = -1;
            
            // Сравниваем числовую метку события
            if($update->update_id > $this->last_update){
                $this->last_update = $update->update_id;
                $hasDoc = false;
                
                if(isset($update->message)){
                    $chat_id = $update->message->chat->id;
                    $user_id = $update->message->from->id;
                    $username = $update->message->from->username;
                    $text = $update->message->text;
                }
                
                if(isset($update->callback_query)){
                    $chat_id = $update->callback_query->message->chat->id;
                    $username = $update->callback_query->from->username;
                    $user_id = $update->callback_query->from->id;
                    $text = $update->callback_query->data;
                    
                    if(isset($update->callback_query->id)){
                        $callback_id = $update->callback_query->id;
                    }
                }
                
                if(isset($update->message->document)){
                    $last_doc = [
                        'type' => 'document',
                        'file_name' => $update->message->document->file_name,
                        'mime_type' => $update->message->document->mime_type,
                        'file_id' => $update->message->document->file_id,
                        'file_size' => $update->message->document->file_size,
                    ];
                    $hasDoc = true;
                }
                                
                if(isset($update->message->photo) && sizeof($update->message->photo) > 0){
                    $last_photo = end($update->message->photo);
                    $last_doc = [
                        'type' => 'photo',
                        'file_name' => 'photo.jpg',
                        'mime_type' => 'image/jpeg',
                        'file_id' => $last_photo->file_id,
                        'file_size' => $last_photo->file_size,
                    ];
                    $hasDoc = true;
                }
                
                if($hasDoc){
                    $text .= ' [attach: '. $last_doc['type'] . '; ' . $last_doc['file_name'] . '; ' . $last_doc['mime_type'] . '; #' . $last_doc['file_id']. '; ' . $last_doc['file_size'] . ' bytes]';
                }
                Debug::info('[INPUT] ' . $username . ': ' . $text);
                
                $command = $this->createCommand($username, $chat_id, $user_id);
                $this->processCommand($text, $command, $callback_id, $last_doc);
                
            }
        } catch (\Exception $e){
            Debug::error('[OnUpdate] ' . get_class($e). ': ' . $e->getMessage());
        }
    }
    
    public function createCommand(string $username, int $chat_id, int $user_id): Commands {        
        // Если ранее пользователь не обращался к боту, создадим ему экземпляр Commands
        if(!isset($this->commands[$chat_id])){        
            $commands = new Commands($this);
            $commands->setChatId($chat_id);
            $commands->setUserId($user_id);
            $commands->setUsername($username);
            $this->commands[$chat_id] = $commands;
        }
        
        return $this->commands[$chat_id];
    }
    
    public function processCommand(string $text, Commands $commands, int $callback_id = -1, ?array $doc = null){
        try {  
            // Проверка, есть ли такой пользователь в списке разрешённых
            if(!$this->checkUser($commands->getUsername())){
                $commands->deniedMsg();
                return;
            }
            
            $this->setUserId($commands->getUsername(), $commands->getUserId());
        
            $cmd = $this->parseCommand($commands->alias($text));
            $hasDoc = is_array($doc) && sizeof($doc) > 0;                    
            $commands->setCallbackInstance($callback_id);
                        
            // Если удалось распасрсить команду
            if(!$hasDoc && $cmd !== false && method_exists($commands, '__' . $cmd['command'])){                                               
                call_user_func_array([$commands, '__' . $cmd['command']], $cmd['args']);
            }
                        
            // Если есть документ
            elseif($hasDoc) {             
                $file = $this->getFile($doc);
                call_user_func_array([$commands, 'inputFileMsg'], [$file, $doc]);
            }
            
            // Если команда неизвестна
            else {
                $commands->undefinedMsg(($cmd['command'] ?? $text));
            }
        }
        catch (Exception | Error $e){
            $emessage = $e->getMessage();
            Debug::error('Command error: [' . get_class($e) .'] ' . $emessage);
            
            // Ошибка об неверном вызове команды
            if(str::contains($emessage, 'Missing argument')){
                $emessage = str::replace($emessage, get_class($commands) . '::__', 'command /');
            }
            
            $commands->errorMsg($emessage);   
        }
    }
    
    public function sendToAll(array $data){
        foreach ($this->users as $user){
            if($user['id'] <= 0) continue;
            
            //$command = $this->createCommand($user['name'], $user['id'], $user['id']);
            $this->sendAnswer($user['id'], $data);
        }
    }
    
    /**
     * Отправка ответа 
     */
    public function sendAnswer($chat_id, $data){
        if(!is_array($data) || sizeof($data) == 0) return;
                
        if(isset($data['callback'])){
            $text = $data['text'] ?? $data['alert'] ?? 'OK';
            $alert = isset($data['alert']);
            $this->api->answerCallbackQuery()->callback_query_id($data['callback'])->text($text)->show_alert($alert)->query();
            return;
        }
        
        if(isset($data['text'])){
           $query = $this->api->sendMessage()->chat_id($chat_id)->text($data['text']);
           if(isset($data['keyboard'])){
               $query->reply_markup($data['keyboard']);
           }
           $query->query();
        }
        
        if(isset($data['photo'])){
            $this->api->sendPhoto()->chat_id($chat_id)->photo(new File($data['photo']))->query();
        }
        
        if(isset($data['doc'])){
            $this->api->sendDocument()->chat_id($chat_id)->document(new File($data['doc']))->query();
        }
    }
    
     
    /**
     * Скачивает и сохраняет последний отправленный пользователем файл 
     */
    public function getFile(array $fileData): File {
        if(!isset($fileData['file_id'])){
            throw new \Exception('Input file not found!');
        }
        
        $file = $this->api->getFile()->file_id($fileData['file_id'])->query();
        $durl = $file->download_url;
        $savePath = app()->appModule()->getAppDownloadDir() . '/' . time() . '_' . basename($durl);
        $save = FileStream::of($savePath, 'w');
        $save->write(Stream::getContents($durl));
        $save->close();
        
        return File::of($savePath);
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