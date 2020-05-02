<?php
namespace main\modules;

use windows;
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
     * @var bool 
     */
    public $batteryCharge;    
    
    /**
     * @var int
     */
    public $batteryLevel;    
    
    /**
     * @var bool
     */
    public $batteryGt5;      
    
    /**
     * @var bool
     */
    public $batteryGt15;  
      
    /**
     * @var bool
     */
    public $batteryGt50;
    
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
        
        // Trigger: battery
        if(Windows::isWin()){
            try{
                $this->batteryLevel = Windows::getBatteryPercent();
                $this->batteryCharge = Windows::isBatteryCharging();
                $this->batteryGt50 = $this->batteryLevel > 50;
                $this->batteryGt15 = $this->batteryLevel > 15;
                $this->batteryGt5 = $this->batteryLevel > 5;  
                
                $this->registerEvent('battery_win', 
                    function($isChargeChanged, $batteryCharge, $isLevelLte50, $isLevelLte15, $isLevelLte5){                
                        if($isChargeChanged){
                            if($batteryCharge){
                                $message = 'Зарядка подключена';
                            } else {
                                $message = 'Зарядка отключена';
                            }
                        }
                        
                        if($isLevelLte50){
                            $message = 'Аккумулятор разряжен до 50%';
                        }      
                                          
                        if($isLevelLte15){
                            $message = 'Аккумулятор разряжен до 15%';
                        }            
                                                      
                        if($isLevelLte5){
                            $message = 'Аккумулятор разряжен до 5%';
                        }
                        
                        $this->appModule()->tgBot->sendToAll(['text' => $message]);
                    },
            
                    function(){
                        $batteryLevel = Windows::getBatteryPercent();
                        $batteryCharge = Windows::isBatteryCharging();
                        $batteryGt50 = $batteryLevel > 50;
                        $batteryGt15 = $batteryLevel > 15;
                        $batteryGt5 = $batteryLevel > 5; 
                        
                        $isChargeChanged = $batteryCharge != $this->batteryCharge;
                        
                        $isLevelLte50 = false;
                        if($batteryLevel <= 50 && $this->batteryGt50 == true){
                            $isLevelLte50 = true;
                            $this->batteryGt50 = false;
                        }
                        elseif($batteryLevel > 50){
                            $this->batteryGt50 = true;
                        }     
                                           
                        $isLevelLte15 = false;
                        if($batteryLevel <= 15 && $this->batteryGt15 == true){
                            $isLevelLte15 = true;
                            $this->isLevelLte15 = false;
                        }
                        elseif($batteryLevel > 15){
                            $this->isLevelLte15 = true;
                        }
                                                                   
                        $isLevelLte5 = false;
                        if($batteryLevel <= 5 && $this->batteryGt5 == true){
                            $isLevelLte5 = true;
                            $this->isLevelLte5 = false;
                        }
                        elseif($batteryLevel > 5){
                            $this->isLevelLte5 = true;
                        }
                        
                        Debug::Log('[Battery trigger] ' . str_replace(["\r\n", "\n", "\r", '  '], ' ', var_export(['batteryLevel' => $batteryLevel, 'isChargeChanged' => $isChargeChanged, 'batteryCharge' => $batteryCharge, 'isLevelLte50' => $isLevelLte50, 'isLevelLte15' => $isLevelLte15, 'isLevelLte5' => $isLevelLte5], true)), 0);
                        
                        $this->batteryLevel = $batteryLevel;
                        if($isChargeChanged || $isLevelLte50 || $isLevelLte15 || $isLevelLte5){
                            $this->batteryCharge = $batteryCharge;
                            return [$isChargeChanged, $batteryCharge, $isLevelLte50, $isLevelLte15, $isLevelLte5];
                        }
                        
                        return false;
                    }
                );
        
            }
            catch (WindowsException $e){
                Debug::warn('[Battery event] Exception: ' . $e->getMessage(), 0);
                $this->disableEvent('battery_win');
            }
        }
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
