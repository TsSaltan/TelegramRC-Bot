<?php
namespace main\modules;

use telegram\object\TMarkup;
use std, gui, framework, main;


class EventsHandler extends AbstractModule
{
    const EVENT_TIME = 10000; // каждые 30 сек
    
    public $events = [];
    
    /**
     * @var Timer 
     */
    public $timer;
    
    /**
     * @var array 
     */
    public $drives;
    
    /**
     * @event action 
     */
    public function eventsConstruct(){    
        $this->timer = Timer::every(self::EVENT_TIME, function(){
            foreach ($this->events as $name => $event){
                $this->triggerCall($name);
            }
        });
        
        // Trigger: program startup
        $this->registerEvent('startup', function(){
            $kb = TMarkup::inlineKeyboard()->button('Время работы', '/uptime');
            $this->appModule()->tgBot->sendToAll(['text' => SMILE_BOT . ' TelegramRC Bot запущен', 'keyboard' => $kb]);
        });
        $this->triggerEvent('startup');
        
        // Trigger: USB-devices
        $this->drives = $this->getDrives();
        $this->registerEvent('usb', 
            function($removed, $added){                
                $message = '';
                if(sizeof($removed) > 0){
                    $message .= "\n" . SMILE_DIAMOND_ORANGE . " USB отключено: " . implode('; ', $removed);
                }                
                
                if(sizeof($added) > 0){
                    $message .= "\n" . SMILE_DIAMOND_BLUE . " USB подключено: " . implode('; ', $added);
                }
                
                $this->appModule()->tgBot->sendToAll(['text' => $message]);
            },
            
            function(){
                $drives = $this->getDrives();
                if($drives != $this->drives){
                    $removed = array_diff($this->drives, $drives);
                    $added = array_diff($drives, $this->drives);
                    $this->drives = $drives;
                    return [$removed, $added];
                } 
                
                return false;
            }
        );
    }
    
    protected function getDrives(){
        $drives = File::listRoots();
        $ds = [];
        
        foreach ($drives as $d){
            /* @var File $d */
            $ds[] = $d->getAbsolutePath();
        }
        
        return $ds;
    }

    public function registerEvent(string $eventName, callable $callback, callable $trigger = null){
        $this->events[$eventName] = ['callback' => $callback, 'trigger' => $trigger];
    }
    
    public function triggerCall(string $eventName) {
        $cfg = $this->getEventsRules();
        if(isset($this->events[$eventName]) && isset($cfg[$eventName]) && $cfg[$eventName] && is_callable($this->events[$eventName]['trigger'])){
            $result = call_user_func($this->events[$eventName]['trigger']);
            Debug::Log('Call trigger "'. $eventName .'". Result: ' . str_replace(["\r\n", "\n", "\r"], ' ', var_export($result, true)), 0);
            if(is_array($result)){
                $this->triggerEvent($eventName, $result);
            }
        }
    }    
    
    public function triggerEvent(string $eventName, array $args = []){
        $cfg = $this->getEventsRules();
        if(isset($this->events[$eventName]) && isset($cfg[$eventName]) && $cfg[$eventName]){
            call_user_func_array($this->events[$eventName]['callback'], $args);
        }
    }
    
    public function enableEvent(string $eventName){
        $cfg = $this->getEventsRules();
        $cfg[$eventName] = true;
        Config::set('events', $cfg);
    }    
    
    public function disableEvent(string $eventName){
        $cfg = $this->getEventsRules();
        $cfg[$eventName] = false;
        Config::set('events', $cfg);
    }
        
    public function isEventEnabled(string $eventName): bool {
        $cfg = $this->getEventsRules();
        return $cfg[$eventName] ?? false;
    }
            
    public function getEventsRules(): array {
        return Config::get('events', []);
    }
}
