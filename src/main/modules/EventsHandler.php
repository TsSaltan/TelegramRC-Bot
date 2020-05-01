<?php
namespace main\modules;

use std, gui, framework, main;


class EventsHandler extends AbstractModule
{
    const EVENT_TIME = 30000; // каждые 30 сек
    
    public $events = [];
    
    /**
     * @var Timer 
     */
    public $timer;
    
    /**
     * @event action 
     */
    public function eventsConstruct(){    
        $this->timer = Timer::every(self::EVENT_TIME, function(){
            foreach ($this->events as $name => $event){
                if($this->triggerCall($name)){
                    $this->triggerEvent($name);
                    
                }
            }
        });
        
        // Trigger: program startup
        $this->registerEvent('startup', function(){
            $this->appModule()->tgBot->sendToAll(['text' => 'TelegramRC Bot запущен !']);
        });
        
        $this->triggerEvent('startup');
    }

    public function registerEvent(string $eventName, callable $callback, callable $trigger = null){
        $this->events[$eventName] = ['callback' => $callback, 'trigger' => $trigger];
    }
    
    public function triggerCall(string $eventName): bool {
        $cfg = $this->getEventsRules();
        if(isset($this->events[$eventName]) && isset($cfg[$eventName]) && $cfg[$eventName] && is_callable($this->events[$eventName]['trigger'])){
            return boolval(call_user_func($this->events[$eventName]['trigger']));
        }
        
        return false;
    }    
    
    public function triggerEvent(string $eventName){
        $cfg = $this->getEventsRules();
        if(isset($this->events[$eventName]) && isset($cfg[$eventName]) && $cfg[$eventName]){
            call_user_func($this->events[$eventName]['callback']);
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
        
    public function isEventEnabled(): bool {
        $cfg = $this->getEventsRules();
        return $cfg[$eventName] ?? false;
    }
            
    public function getEventsRules(): array {
        return Config::get('events', []);
    }
}
