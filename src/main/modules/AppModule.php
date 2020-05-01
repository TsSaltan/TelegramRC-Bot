<?php
namespace main\modules;

use std, gui, framework, main;


class AppModule extends AbstractModule
{
    const APP_VERSION = '4.0-dev';
    
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
     * @var string 
     */
    public $lockFile = 'app.lock';
    
    /**
     * Запуск программы
     * @event action  
     */
    public function сonstruct(){           
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
        
                
        $this->singleRun();
        
        // Загрузка настроек из файла    
        Config::load();
        
        // Если в конфиге нет токена, открываем форму установки 
        if(Config::isInstallRequired()){
            $setup = $this->form('Setup');
             
            // При запуске первым аргументом можно передать токен 
            if(isset($GLOBALS['argv'][1])){
                $setup->edit_token->text = $GLOBALS['argv'][1];
            }
                         
            // Вторым аргументом - имя пользователя 
            if(isset($GLOBALS['argv'][2])){
                $setup->edit_user->text = $GLOBALS['argv'][2];
            }
             
            return $setup->show();
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
        
        $api_url = Config::get('api_url');
        if(str::startsWith($api_url, 'https://')){
            $this->tgBot->setApiURL($api_url);
        }
        
        /** @var Params $form */
        $form = $this->form('Params'); 
   
        $this->tgBot->setErrorCallback(function($e) use ($form){
            $className = basename(get_class($e));
            $errMsg = $e->getMessage();
            $this->notify('Произошла ошибка: [' . $className . '] ' . $errMsg, 'ERROR');
            
            if(str::contains($errMsg, 'Parse error')){
                Debug::info("Raw query: " . $this->tgBot->getApi()->getRawResponse());
            }
            
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
            
            uiLater(function(){
                /* @var Params $form */            
                $form = $this->form('Params');
                if($form->visible){
                    $form->updateBotInfo();
                    $form->setStartButton('on');
                }
            });
        });
                
        $this->tgBot->setStopCallback(function(){
            $this->systemTray->icon = new UXImage('res://.data/img/plane_warn.png');
            $this->systemTray->tooltip = $this->trayTooltop . ': Offline';            
            
            uiLater(function(){
                /* @var Params $form */            
                $form = $this->form('Params');
                if($form->visible){
                    $form->setStartButton('off');
                }
            });
        });     
                  
        if(Config::get('autorun')){
            $this->tgBot->startListener();    
        }
        
        if(!Config::get('iconified')){
            $form->show();
        }
        
        $minutes = Config::get('restart_minutes');
        if(Config::get('restart') === true && intval($minutes) > 0){
            $this->setRestartTime($minutes);
        }
    }
    
    public function singleRun(){
        /* @var File $file */
        $file = File::of($this->getAppDir() . $this->lockFile);
        if($file->exists()){
            if(!$file->delete()){
                Debug::error('Program already started!');
                alert('Программа уже запущена! Возможна работа только одной копии программы.');
                $this->shutdown();
            }
        }
        
        $file->createNewFile();
        $stream = FileStream::of($file->getAbsolutePath(), 'w');
        $stream->write(1);
    }

    public function shutdown(){
        $this->systemTray->visible = false;
        $this->systemTray->manualExit = false;
        app()->shutdown();
        exit(0);
    }
    
    /**
     * @event systemTray.click 
     */
    public function doSystemTrayClick(){    
        $form = $this->form('Params');
        if($form->visible){
            $form->free();
        } else {
            $form->show();
        }
    }
    
    public function getAppDir(){
        $ds = System::getProperty('file.separator');
        return System::getProperty('user.home') . $ds . 'TelegramRemoteBot'. $ds;
    }    
    
    public function getAppDownloadDir(){
        $ds = System::getProperty('file.separator');
        return $this->getAppDir(). $ds . 'download';
    }
    
    public function getCurrentDir() : string {
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
    
    /**
     * @var Timer
     **/
    public $restarter;
    
    /**
     * Установить время для автоматического переподключения
     * @var int $minutes Количество минут. Если -1, то переподключения не будет 
     */
    public function setRestartTime(int $minutes){
        if($this->restarter instanceof Timer){
            $this->restarter->cancel();    
        }
        
        if($minutes > 0){
             $this->restarter = Timer::every($minutes * 60000, function() use ($minutes){
                 Debug::info('Automatic reconnection (after '. $minutes.' minute(s))');
                 $this->tgBot->stopListener();  
                 waitAsync(3000, function(){
                     $this->tgBot->startListener();  
                 });
             });
        }
    }
}
