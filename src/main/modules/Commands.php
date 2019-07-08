<?php
namespace main\modules;

use webcam\Webcam;
use Exception;
use windows;
use std, gui, framework, main;

/**
 * Здесь хранятся все доступные команды
 * Каждому пользователю создаётся экземпляр данного класса
 * 
 * Имя команды преобразуется в имя метода
 * /имя-команды => __имя_метода
 * 
 * Данные после команды передаются как аргументы функции
 * т.е. test 123 "456 789"
 * будут переданы как 
 * Commands->test('123', '456 789');
 * 
 * Чтоб послать ответ, можно воспользоваться двумя путями:
 * 1. Функция должна вернуть массив вида
 * ['text' => 'текст для ответа'] или
 * ['photo' => 'path/to/file.jpeg'] или
 * ['doc' => 'path/to/file.doc']
 * 
 * 2. Можно  самому вызвать метод $this->send($text) или $this->sendPhoto($file) и т.д.
 */
class Commands extends AbstractModule {
   
    /**
     * @var int 
     */
    public $chat_id;
    
    /**
     * @var string 
     */
    public $username;
    
    /**
     * @var TelegramBot 
     */
    public $bot;
    
    /**
     * Текущая директория используется в командах cd, ls, cat, get-file
     */
    public $dir = '/';
    
    public function __construct($chat_id = -1, $username = null, ?TelegramBot $bot = null){
        $this->chat_id = $chat_id;
        $this->username = $username;
        $this->bot = $bot;
    }
    
    /**
     * Отправить сообщение 
     */
    public function send($text){
        $this->bot->sendAnswer($this->chat_id, ['text' => $text]);
    }    
    
    /**
     * Отправить фотографию 
     */
    public function sendPhoto($photo){
        $this->bot->sendAnswer($this->chat_id, ['photo' => $photo]);
    }   
     
    /**
     * Отправить файл / документ 
     */ 
    public function sendDoc($doc){
        $this->bot->sendAnswer($this->chat_id, ['doc' => $doc]);
    }
    
    /**
     * Сообщение об неизвестной команде 
     */
    public function undefinedMsg($cmd = null){
        return ['text' => 'Неизвестная команда "' . $cmd . '"'];        
    }
        
    /**
     * Сообщение при возникшей ошибке 
     */    
    public function errorMsg($e){
        return ['text' => 'Произошла ошибка во время выполнения команды: ' . $e];        
    }
    
    /**
     * Сообщение доступ запрещён 
     */
    public function deniedMsg(){
        return ['text' => 'Извините, но у вас нет доступа к данному боту. Если вы хотите управлять ботом, внесите имя пользователя "' . $this->username . '" в список разрешённых'];        
    }
    
    public function checkWin(){
        if(!Windows::isWin()) throw new \Exception('Required Windows OS');
    }   

    
    /**
     * Команда /start 
     * Приветствие при запуске бота
     */
    public function __start(){
        return ['text' => 'Вас приветствует бот для удалённого управления компьютером. Введите /help для получения справки.'];        
    }
    
    /**
     * Команда /help
     * Справка / помощь
     */
    public function __help(){
        $isWin = Windows::isWin();

        $text = "Версия бота: " . AppModule::APP_VERSION . " \n";

        $text .= "\n- Команды -\n";
        $text .= "Команда необязательно должна начинаться со слеша /\n";
        $text .= "т.е. разницы между командами /cd и cd - нет.\n";
        $text .= "Аргумены передаются через знак пробел. Если аргумент содержит пробел, его нужно обрамить в двойные кавычки \".\n";
        $text .= "Также аргумент можно передать через __.\n";
        $text .= "Примеры:\n";
        $text .= "/command \"argument 1\" arg2\n";
        $text .= "/command__0__1__2\n";
        $text .= "/photo 0 == /photo__0\n";

        $text .= "\n- Список доступных команд -\n";
        $text .= "-- Общее --\n";
        $text .= "/start - Приветствие бота\n";
        $text .= "/help - Текущая справка\n";
        $text .= "/ip - Получить внешний ip\n";

        $text .= "\n-- Система --\n";
        $text .= "/exec [cmd] - Выполнить команду\n";
        $text .= "/osinfo - Информация об ОС\n";
            
        $text .= "\n-- Файловая система --\n";
        $text .= "/cd - Получить текущую директорию\n";
        $text .= "/cd [path] - Указать текущую директорию\n";
        $text .= "/ls - Показать содержимое текущей директории\n";
        $text .= "/cat [file] - Напечатать содержимое файла\n";
        $text .= "/get_file [file] - Скачать файл\n";

        $text .= "\n-- Медиа --\n";
        $text .= "/screens - Информация об экранах\n";
        $text .= "/screenshot - Сделать скриншот экрана по умолчанию\n";
        $text .= "/screenshot [n] - Сделать скриншот экрана из списка (/screens)\n";
        $text .= "/cameras - Вывести список web-камер\n";
        $text .= "/photo - Сделать фото с web-камеры по умолчанию\n";
        $text .= "/photo [n] - Сделать фото с выбравнной из списка (/cameras) web-камеры\n";
            
        // Некоторые функции будут работать только на Windows       
        if($isWin){
            $text .= "\n-- Кнопки --\n";     
            $text .= "/media - Список доступных медиа кнопок\n";
            $text .= "/play - Воспроизведение / пауза\n";
            $text .= "/stop - Остановить воспроизведение\n";
            $text .= "/next - Следующий трек\n";
            $text .= "/prev - Предыдущий трек\n";
            $text .= "/volu - Громкость+\n";
            $text .= "/vold - Громкость-\n";
            
            $text .= "\n-- Железо --\n";
            // $text .= "/hardware - Железо компьютера\n"; // @todo
            $text .= "/ram - Оперативная память\n";
            $text .= "/battery - Информация об аккумуляторе\n";
            $text .= "/temperature - Датчики температуры\n";
            
            $text .= "\n-- Питание --\n";
            $text .= "/reboot - Перезагрузить ПК\n";
            $text .= "/shutdown - Выключить ПК\n";
            
            $text .= "\n-- Дополнительно --\n";
            $text .= "/uptime - Время работы ПК\n";
            $text .= "/volume - Получить уровень громкости\n";
            $text .= "/volume [0-100] - Установить уровень громкости\n";
            $text .= "/brightness - Получить уровень яркости\n";
            $text .= "/brightness [0-100] - Установить уровень яркости\n";
        }
        
        return ['text' => $text];        
    }    
    
    /**
     * Команда /cd
     * Смена/отображение текущей директории 
     */
    public function __ip(){
        $this->send("IP info: " . file_get_contents('http://ipinfo.io/json'));
    }  
    
    /**
     * Команда /osinfo
     * Информация об операционной системе
     */
    public function __osinfo(){
        $info = "Название ОС: " . System::getProperty('os.name') . "\n";
        $info.= "Архитектура: " . System::getProperty('os.arch') . "\n";
        $info.= "Версия: " . System::getProperty('os.version') . "\n";
        $info.= "Имя пользователя: " . System::getProperty('user.name') . "\n";
        $info.= "Предпочитаемый язык: " . System::getProperty('user.language') . "\n";
        $info.= "Домашняя папка: " . System::getProperty('user.home') . "\n";
        $info.= "Страна: " . System::getProperty('user.country') . "\n";
        
        $this->send($info);
    }     
    
    /**
     * Команда /cd
     * Смена/отображение текущей директории 
     */
    public function __cd($path = null){
        if(!is_null($path)){
            if($path == '/' || $path == '\\' || substr($path, 1, 1) == ':'){
               $this->dir = $path;
            }
            else $this->dir = realpath($this->dir . '/' . $path);
        }
        
        return ['text' => 'Текущая директория: ' . $this->dir];
    }
    
    /**
     * Команда /ls
     * Отображение содержимого текущей директории 
     */
    public function __ls(){
        if(is_null($this->dir) || $this->dir == "/" || $this->dir == "\\"){
            $roots = array_map(function($e){ return $e->getAbsolutePath(); }, File::listRoots());
        } else {
            $roots = File::of($this->dir)->find();
        }
        
        $list = "Содержимое директории " . $this->dir . ' :';
        foreach($roots as $root){
            $list .= "\n- $root";
        }
        
        return ['text' => $list];
    }    
    
    /**
     * Команда /cat
     * Выводит содержимое (текстового) файла 
     */
    public function __cat($file = null){
        $path = null;
        if(!is_null($file)){
            $path = realpath($this->dir . '/' . $file);
        }
        
        if(file_exists($path)){
            return ['text' => file_get_contents($path)];
        } else {
            return ['text' => 'Неверный путь: ' . $path];
        }
    }
        
    /**
     * Команда /get_file
     * Отдаёт файл на сксчивание пользователю 
     */    
    public function __get_file($file = null){
        $path = null;
        if(!is_null($file)){
            $path = realpath($this->dir . '/' . $file);
        }
        
        if(file_exists($path)){
            return ['doc' => $path];
        } else {
            return ['text' => 'Неверный путь: ' . $path];
        }
    }
        
    /**
     * Команда /screens 
     */
    public function __screens(){
        $screens = UXScreen::getScreens();
        $info = "Количество экранов': " . sizeof($screens) . ".\n";
        
        foreach($screens as $i => $screen){
            $info .= " $i. " . $screen->bounds['width'] . "x" . $screen->bounds['height'] . ", позиция: " . $screen->bounds['x'] . "x" . $screen->bounds['y'] . ", DPI: " . $screen->dpi . ". [📷 /screenshot__$i]\n";
        }
        
        $this->send($info);
    }  
    
    /**
     * Команда /screenshot 
     */
    public function __screenshot(int $screenN = 0){
    
        $screens = UXScreen::getScreens();
        if(!isset($screens[$screenN])) return $this->send("Ошибка: экран №$screenN не существует! Укажите номер экрана из спиcка /screens");
        
        $screen = $screens[$screenN];
                
        $this->send('Делаю снимок экрана...');
        $file = File::createTemp('screenshot', '.png');
        Debug::info('Make screenshot to ' . $file->getAbsolutePath());
        
        app()->appModule()->robot->screenshot($screen->bounds, $screen)->save($file);
        
        $this->sendDoc($file->getAbsolutePath());
    }    
        
    /**
     * Команда /cameras
     */
    public function __cameras(){
    
        $cameras = Webcam::getWebcams();
        if(sizeof($cameras) == 0){
            $list = "Web-камеры не обнаружены";
        } else {
            $list = "Список установленных камер:";
            foreach($cameras as $n => $camera){
                $list .= "\n $n. " . $camera->name . " [📷 /photo__$n]";
            }
        }
          
        $this->send($list);
    }   
           
    /**
     * Команда /photo
     * Могут вываливаться ошибки, но вроде работает нормально 
     * @param int $camN - Номер камеры в списке камер
     */
    public function __photo(int $camN = -1){
        $cameras = Webcam::getWebcams();
        if($camN > sizeof($cameras)) return $this->send('Указана несуществующая камера. Список камер доступен по команде /cameras');
        $camera = $cameras[$camN];
        
        $file = File::createTemp('shot', '.png');
        $this->send('Делаю снимок c камеры ' . $camera->name . '...');
        $camera->open();
        $camera->getImage()->save($file);
        $camera->close();
        return ['photo' => $file->getAbsolutePath()];     
    }   
    
    /**
     * Команда /temperature 
     * Получить данные о температуре
     * Только для Windows
     */
    public function __temperature(){
        $this->checkWin();
        $this->send('Получаю данные с датчиков...');
        $t = Windows::getTemperature();
        $res = "Температурные датчики: ";
        if(sizeof($t) == 0) $res.='недоступны.';
        foreach($t as $s){
            $name = strlen($s['name']) < 15 ? $s['name'] : (substr($s['name'], 0, 13) . '...');
            $res .= "\n- " . $s['location'] . '/' . $name . ': ' . $s['temp'] . '°C';
        }
        $this->send($res);
    }
    
    /**
     * Команда /ram 
     * Получить данные об свободной и занятой оперативной памяти
     * Только для Windows
     */
    public function __ram(){
        $this->checkWin();
        $total = Windows::getTotalRAM();
        $free = Windows::getFreeRAM();
        $perc = round($free / $total * 100);
        $msg = "Всего оперативной памяти: " . round($total / 1024 / 1024, 2) . "MiB\n";
        $msg.= "Свободно: ". round($free / 1024 / 1024, 2) . "MiB (" . $perc . "%)";
        $this->send($msg);
    }
        
    /**
     * Команда /volume [0-100] 
     * Получить или изменить уровень громкости
     * Только для Windows
     */
    public function __volume(?int $level = null){
        $this->checkWin();
        try{
            if(is_int($level) && $level >= 0 && $level <= 100){
                Windows::setVolumeLevel($level);
                $this->send('Установлен уровень громкости: ' . $level);
            } else {
                $this->send('Текущий уровень громкости: ' . Windows::getVolumeLevel());
            }
        } catch (WindowsException $e){
            $this->send('Ошибка: Управление громкостью недоступно на данном устройстве');
        }
    }
   
            
    /**
     * Команда /brightness [0-100] 
     * Получить или изменить уровень яркости экрана
     * Только для Windows
     */
    public function __brightness(?int $level = null){
        $this->checkWin();
        try{
            if(is_int($level) && $level >= 0 && $level <= 100){
                Windows::setBrightnessLevel($level);
                $this->send('Установлен уровень ярности: ' . $level);
            } else {
                $this->send('Текущий уровень яркости: ' . Windows::getBrightnessLevel());
            }
        } catch (WindowsException $e){
            $this->send('Ошибка: Управление яркостью недоступно на данном устройстве');
        }
    }    
                
    /**
     * Команда /shutdown
     * Выключить ПК
     * Только для Windows
     */
    public function __shutdown(){
        $this->checkWin();
        $this->send('Отправлен запрос на выключение компьютера');
        
        uiLater(function(){
            $confirm = app()->getForm('ConfirmTimeout');
            $confirm->setButtons('Выключить ПК', 'Отмена');
            $confirm->setText('Выключение ПК', 'Поступила команда на выключение компьютера. Подтвердите действие либо компьютер выключится автоматически.');
            $confirm->start(10, function(){
                try{
                    $this->send('Завершение работы');
                    Windows::shutdown();
                            
                } catch (WindowsException $e){
                    $this->send('Ошибка: не удалось выключить ПК');
                } 
            }, function(){
                $this->send('Отменено пользователем');
            });
        });
    }    
                    
    /**
     * Команда /reboot
     * Перезагрузить ПК
     * Только для Windows
     */
    public function __reboot(){
        $this->checkWin();
        $this->send('Отправлен запрос на перезагрузку компьютера');
        
        uiLater(function(){
            $confirm = app()->getForm('ConfirmTimeout');
            $confirm->setButtons('Перезагрузить ПК', 'Отмена');
            $confirm->setText('Перезагрузка ПК', 'Поступила команда на перезагрузку компьютера. Подтвердите действие либо компьютер перезагрузится автоматически.');
            $confirm->start(10, function(){
                try{
                    $this->send('Перезагрузка');
                    Windows::reboot();
                            
                } catch (WindowsException $e){
                    $this->send('Ошибка: не удалось перезагрузить ПК');
                } 
            }, function(){
                $this->send('Отменено пользователем');
            });
        });
        $this->checkWin();
    }
    
    public function __exec(){
        try{
            $cmd = implode(' ', func_get_args());
            if(Windows::isWin()){
                $result = WindowsScriptHost::cmd($cmd);
            } else {    
                /** @var Process $res */
                $exec = execute($cmd);
                $exec = $exec->start();
                $result = $exec->getInput()->readFully();
            }                    
        } catch (\Exception $e){
            $result = 'Exec error: [' . get_class($e) . '] ' . $e->getMessage();
        }
        
        $this->send($result);
    }
     
    public function __media(){
        $this->checkWin();
        try {
            $level = Windows::getVolumeLevel();
            $media = "/vold 🔽 🔉 $level% 🔼 /volu";
        } catch (WindowsException $e){
            $media = "/vold 🔽 🔼 /volu";
        }
        $this->send($media . "\n/prev ⏪ ⏩ /next \n/stop ⏹ ⏯ /play");
    }   
     
    public function __play(){
        $this->checkWin();
        Windows::pressKey(Windows::VK_MEDIA_PLAY_PAUSE);
        $this->__media();
    } 
    
    public function __stop(){
        $this->checkWin();
        Windows::pressKey(178);
        $this->__media();
    } 
        
    public function __prev(){
        $this->checkWin();
        Windows::pressKey(Windows::VK_MEDIA_PREV_TRACK);
        $this->__media();
    }         
    
    public function __next(){
        $this->checkWin();
        Windows::pressKey(Windows::VK_MEDIA_NEXT_TRACK);
        $this->__media();
    } 
        
    public function __volu(){
        $this->checkWin();
        Windows::pressKey(Windows::VK_VOLUME_UP);
        $this->__media();
    }      
      
    public function __vold(){
        $this->checkWin();
        Windows::pressKey(Windows::VK_VOLUME_DOWN);
        $this->__media();
    }
          
    public function __uptime(){
        $this->checkWin();
        $bootTime = Windows::getUptime(); 
        $programTime = (time() - app()->appModule()->startup) * 1000;
        
        $btime = new Time($bootTime, TimeZone::UTC()); 
        $ptime = new Time($programTime, TimeZone::UTC()); 
        
        $this->send(
            'Компьютер работает: ' . ($btime->day() - 1) . ' дней ' . $btime->hourOfDay() . ' часов ' . $btime->minute() . ' минут ' . $btime->second() . " секунд.\n" .
            'Проргамма работает: ' . ($ptime->day() - 1) . ' дней ' . $ptime->hourOfDay() . ' часов ' . $ptime->minute() . ' минут ' . $ptime->second() . " секунд." 
        );
    }         
      
    public function __battery(){
        $this->checkWin();
        try {
            $perc = Windows::getBatteryPercent();
            $voltage = Windows::getBatteryVoltage();
            $isCharge = Windows::isBatteryCharging();
            $rtime = Windows::getBatteryTimeRemaining();
            $time = new Time($rtime, TimeZone::UTC()); 
            
            $this->send(
                "Текущий заряд: " . $perc . "%\n" .
                "Напряжение: " . $voltage . "mV\n" .
                "Заряжается: " . ($isCharge ? 'Да': 'Нет') . "\n" .
                "Оставшееся время работы: " . ($time->day() - 1) . ' дней ' . $time->hourOfDay() . ' часов ' . $time->minute() . ' минут ' . $time->second() . ' секунд'
            );
            
        } catch (WindowsException $e){
            $this->send('Аккумулятор не установлен');
        }
    } 
}