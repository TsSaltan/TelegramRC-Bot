<?php
namespace main\modules;

use std, gui, framework, main;


class AppModule extends AbstractModule
{
    const APP_VERSION = '2.1';
    
    /**
     * Время запуска программы 
     */
    public $startup;
    
    /**
     * @var TelegramBot 
     */
    public $tgBot;
    
    /**
     * @var string
     */
    public $trayTooltop;
    
    /**
     * Запуск программы
     * @event action  
     */
    function сonstruct(){           
        $this->startup = time(); 
        $this->trayTooltop = "TelegramRC (v " . self::APP_VERSION . ')';
        // Создаём папку для прорграммы в домашней директории текущего пользователя (туда всегда разрешена запись)
        $app_dir = $this->getAppDir();       
        if(!fs::exists($app_dir)){
            fs::makeDir($app_dir);
        }  
              
        // Директория для скачивания файлов
        $dwn_dir = $this->getAppDownloadDir();
        if(!fs::exists($dwn_dir)){
            fs::makeDir($dwn_dir);
        }     
        
        // Путь к конфигам и логам
        Config::$cfgFile = $app_dir . Config::$cfgFile;
        Debug::$logFile = $app_dir . Debug::$logFile;
        
        // Загрузка настроек из файла    
        Config::load();
        if(Config::isInstallRequired()){
            // Если в конфиге нет токена, открываем форму установки 
            return $this->loadForm('Setup');
        }
        
        Debug::$saveLogs = Config::get('save_logs');
        Debug::info('Application started. Version ' . self::APP_VERSION);
                    
        $this->tgBot = new TelegramBot;
        $this->tgBot->initBot(Config::get('token'));
        $this->tgBot->setUsers(Config::get('users'));
        
        $proxy = Config::get('proxy');
        if(is_array($proxy)){
            $pr = new Proxy($proxy['type'], $proxy['host'], $proxy['port']);
            $this->tgBot->setProxy($pr);
        }

        /** @var Params $form */
        $form = $this->form('Params'); 
   
        $this->tgBot->setErrorCallback(function($e) use ($form){
            $this->notify('Произошла ошибка: [' . get_class($e) . '] ' . $e->getMessage(), 'ERROR');
            
            if($form->visible){
               $form->setStartButton('off'); 
            }
            
            // Переподключение, если программа скрыта
            if(!$form->visible){
            waitAsync(5000, function() use ($form){
                if(!$form->visible){
                    Debug::info('Try to reconnect after error (hidden)...');
                    $this->tgBot->startListener();  
                }
            });
            }
        });
        
        $this->tgBot->setStartCallback(function(){
            $this->systemTray->icon = new UXImage('res://.data/img/plane_arrow.png');
            $this->systemTray->tooltip = $this->trayTooltop . ': Online';
        });
                
        $this->tgBot->setStopCallback(function(){
            $this->systemTray->icon = new UXImage('res://.data/img/plane_warn.png');
            $this->systemTray->tooltip = $this->trayTooltop . ': Offline';
        });     
                  
        if(Config::get('autorun', $value)){
            $this->tgBot->startListener();    
        }
        
        if(!Config::get('iconified')){
            $form->show();
        }
    }

    function shutdown(){
        $this->systemTray->visible = false;
        $this->systemTray->manualExit = false;
        app()->shutdown();
        exit(0);
    }
    
    /**
     * @event systemTray.click 
     */
    function doSystemTrayClick(){    
        $form = $this->form('Params');
        if($form->visible){
            $form->free();
        } else {
            $form->show();
        }
    }
    
    function getAppDir(){
        $ds = System::getProperty('file.separator');
        return System::getProperty('user.home') . $ds . 'TelegramRemoteBot'. $ds;
    }    
    
    function getAppDownloadDir(){
        $ds = System::getProperty('file.separator');
        return $this->getAppDir(). $ds . 'download';
    }
    
    function getCurrentDir() : string {
        $ds = System::getProperty('file.separator');
        $path = System::getProperty("java.class.path");
        $sep = System::getProperty("path.separator");
        return dirname(realpath(str::split($path, $sep)[0])) . $ds;
    } 
    
    public function notify(string $text, ?string $title = null){   
        uiLater(function() use ($text, $title){
            $noticeForm = $this->form('NotifyForm');
            $noticeForm->setText($text);
            if(strlen($title) > 0) $noticeForm->setTitleText($title);
            $noticeForm->show();
        });
    }
}
