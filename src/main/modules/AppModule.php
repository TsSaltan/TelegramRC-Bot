<?php
namespace main\modules;

use std, gui, framework, main;


class AppModule extends AbstractModule
{
    const APP_VERSION = '1.0';
    
    /**
     * Время запуска программы 
     */
    public $startup;
    
    /**
     * @var TelegramBot 
     */
    public $tgBot;
    
    /**
     * Запуск программы
     * @event action  
     */
    function сonstruct(){         
        $this->startup = time(); 
        $this->systemTray->tooltip = "TelegramRC v " . self::APP_VERSION;
        
        // Создаём папку для прорграммы в домашней директории текущего пользователя (туда всегда разрешена запись)
        $app_dir = $this->getAppDir();
        if(!fs::exists($app_dir)){
            fs::makeDir($app_dir);
        }
        
        // Путь к конфигам и логам
        Config::$cfgFile = $app_dir . basename(Config::$cfgFile);
        Debug::$logFile = $app_dir . basename(Debug::$logFile);
        
        // Загрузка настроек из файла    
        Config::load();
        if(Config::isInstallRequired()){
            // Если в конфиге нет токена, открываем форму установки 
            return $this->loadForm('Install');
        }
        
        Debug::info('Application started. Version ' . self::APP_VERSION);
        
        // Настройка: закрывать в трей
        if(Config::get('use_tray')){
            $this->systemTray->visible = true;
        }
                  
        // Инициализация бота          
        $this->tgBot = new TelegramBot;
        $this->tgBot->initBot(Config::get('token'));
        $this->tgBot->setUsers(Config::get('users'));

        
        /** @var Params $form */
        $form = $this->form('Params'); 
   
        // Обработчик ошибок, возникших при работе проргаммы
        $this->tgBot->setErrorCallback(function($e) use ($form){
            $this->notify('Произошла ошибка: [' . get_class($e) . '] ' . $e->getMessage(), 'ERROR');
            
            if($form->visible){
               $form->setStartButton('off'); 
            }
            
            // Если программа скрыта, автоматически попробуем переподключиться
            if(!$form->visible){
            waitAsync(5000, function() use ($form){
                if(!$form->visible){
                    Debug::info('Try to reconnect after error (hidden)...');
                    $this->tgBot->startListener();  
                }
            });
            }
        });     
                  
        // Настройка: автоматически активировать бота при запуске
        if(Config::get('autorun', $value)){
            $this->tgBot->startListener();    
        }
        
        // Настройка: запускать свёрнутым
        if(!Config::get('iconified')){
            // Если опция выключена, то показываем окно
            $form->show();
        } elseif(!Config::get('use_tray')){
            // Если нет трея, но нужно запустить свернутым, то показываем окно и сворачиваем его
            $form->show();
            $form->iconified = true;
        }
    }

    
    /**
     * При клике по иконке в трее показываем или скрываем окно
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
    
    /**
     * Путь к директории с конфигами и логами
     */
    function getAppDir(){
        $ds = System::getProperty('file.separator');
        return System::getProperty('user.home') . $ds . 'TelegramRC-Bot'. $ds;
    }

    /**
     * Показать уведомление в трее 
     */
    public function notify($text, $type = "NOTICE"){
        $notify = new UXTrayNotification;    
        $notify->title = "TelegramRC Bot";
        $notify->message = $text;
        $notify->notificationType = $type;
        $notify->on('click', function(){
            /** @var Params $form */
            $form = $this->form('Params');
            if(!$form->visible){
                $form->show();
            }
            $form->requestFocus();
        });
        
        $notify->show();
    }
}
