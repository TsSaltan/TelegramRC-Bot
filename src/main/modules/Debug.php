<?php
namespace main\modules;

use std, gui, framework, main;


class Debug extends AbstractModule {
    
    public static $logFile = 'log.txt';

    /**
     * levels
     * 0 - Debug
     * 1 - Info 
     * 2 - Warning
     * 3 - Error
     */ 
    public static function Log($message, $level = 0){
        $diff = self::getTimeDiff();
        $date = '[' . (Time::now()->toString('HH:mm:ss')) . '|+' . $diff . 's] ';
        $fulldate = '[' . (Time::now()->toString('YYYY-MM-dd HH:mm:ss')) . ' | +' . $diff . 's] ';
        
        switch($level){
            case 3:
                $color = UXColor::of('#b31a1a');
                $prefix = 'Error';
                Logger::error($date . $message);
                break;
                    
            case 2:
                $color = UXColor::of('#cc6633');
                $prefix = 'Warn';
                Logger::warn($date . $message);
                break;
                    
            case 1:
                $color = UXColor::of('#4d66cc');
                $prefix = 'Info';
                Logger::info($date . $message);
                break;           
                
            default:
                $color = UXColor::of('#808080');
                $prefix = 'Debug';
                Logger::debug($date . $message);
        }
        
        uiLater(function() use ($level, $color, $date, $fulldate, $message, $prefix){
            $form = app()->getForm('Params');
            if($form->visible){
                $form->label_debug->text = $date . '['.$prefix.'] ' . $message;
                $form->label_debug->textColor = $color;
                $form->text_debug->text .= $fulldate . '['.$prefix.'] ' . $message . "\n";
                $form->text_debug->end();
            }
            
            
        });
        file_put_contents(self::$logFile, $fulldate . '['.$prefix.'] ' . $message . "\n", FILE_APPEND);
        
    }
    
    public static function getLogs(){
        if(file_exists(self::$logFile)){
            return file_get_contents(self::$logFile);
        }
        
        return null;
    }
      
    public static function clearLogs(){
        file_put_contents(self::$logFile, null);
    }
    
    public static function info($m){
        return self::Log($m, 1);
    }
        
    public static function warn($m){
        return self::Log($m, 2);
    }
            
    public static function warning($m){
        return self::Log($m, 2);
    }
            
    public static function error($m){
        return self::Log($m, 3);
    }
    
    public static $lastTime = 0;
    public static function getTimeDiff(): int {
        $diff = (self::$lastTime > 0) ? time() - self::$lastTime : 0;
        self::$lastTime = time();
        return $diff;
    }
    
}