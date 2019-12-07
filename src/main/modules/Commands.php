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
     * Клавиатура над полем ввода 
     */
    protected function keyboard(array $lines){
        return $this->makeKeyboard('keyboard', $lines);
    }    
    
    /**
     * Клавиатура под сообщением
     */
    protected function keyboardInline(array $lines){
        return $this->makeKeyboard('inline_keyboard', $lines);
    }
    
    /**
     * Создать текстовую клавиатуру
     * @param string $type keyboard|inline_keyboard
     * @param array $lines [[command => title]]
     * @param array $params [one_time_keyboard, resize_keyboard]
     * ...
     */
    protected function makeKeyboard($type, array $lines, array $params = []): array {
        $keyboard = array_merge([
            'one_time_keyboard' => true,
            'resize_keyboard' => false
        ], $params);
        
        $keyboard = $params;
  
        foreach ($lines as $line){
            $current_line = [];
            foreach ($line as $cmd => $text){
                if(strpos($cmd, 'http:') !== false || strpos($cmd, 'https:') !== false){
                    $current_line[] = [
                        "text" => $text,
                        "url" => $cmd
                    ];
                } else {
                    $current_line[] = [
                        "text" => $text,
                        "callback_data" => $cmd
                    ];
                }
            }
            
            $keyboard[$type][] = $current_line;
        }
        
        return $keyboard;
    }
    
    /**
     * Клавиатура по умолчанию (над полем ввода) 
     */
    protected function getMainKeyboard(){
        $isWin = Windows::isWin();
        $kb = [
            ['/help' => 'Help 🆘', '/osinfo' => 'OSInfo 💻', '/ip' => 'IP info 🌐'],
            ['/screens' => 'Screens 🖥', '/cameras' => 'Cameras 📷', '/ls' => 'ls / 🗂'],
        ];
        
        if($isWin){
            $kb[] = ['/media' => 'Media RC 🎛 ', '/volume' => 'Volume 🔉', '/brightness' => 'Brightness 🔅']; //  🔆
            $kb[] = ['/battery' => 'Battery 🔋', '/reboot' => 'Reboot 🔄', '/shutdown' => 'Shutdown 🛑'];
        }
        
        return $this->keyboard($kb);       
    }
    
    /**
     * Отправить сообщение 
     */
    public function send($text, ?array $keyboard = null){
        $this->bot->sendAnswer($this->chat_id, ['text' => $text, 'keyboard' => $keyboard]);
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
     * Сообщение при получении файла
     */    
    public function inputFile(array $doc){
        $this->__file('input://');
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
        return ['text' => 'Вас приветствует бот для удалённого управления компьютером. Введите /help для получения справки.', 'keyboard' => $this->getMainKeyboard()];        
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
        $text .= "Также аргумент можно передать через __\n";
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
        $text .= "/file [file] - Отобразить информацию о файле\n";
        if($isWin){
            $text .= "/print [file] - Отправить файл на печать \n";
        }
        $text .= "/download [file] - Скачать файл\n";
        $text .= "/delete [file] - Удалить файл\n";

        $text .= "\n-- Медиа --\n";
        $text .= "/screens - Информация об экранах\n";
        $text .= "/screenshot - Сделать скриншот экрана по умолчанию\n";
        $text .= "/screenshot [n] - Сделать скриншот экрана из списка (/screens)\n";
        $text .= "/cameras - Вывести список web-камер\n";
        $text .= "/photo - Сделать фото с web-камеры по умолчанию\n";
        $text .= "/photo [n] - Сделать фото с выбравнной из списка (/cameras) web-камеры\n";
               
        if($isWin){
            $text .= "\n-- Кнопки --\n";     
            $text .= "/media - Список доступных медиа кнопок\n";
            $text .= "/key__play - Воспроизведение / пауза\n";
            $text .= "/key__stop - Остановить воспроизведение\n";
            $text .= "/key__next - Следующий трек\n";
            $text .= "/key__prev - Предыдущий трек\n";
            
            $text .= "\n-- Железо --\n";
            //$text .= "/hardware - Железо компьютера\n";
            $text .= "/ram - Оперативная память\n";
            $text .= "/battery - Информация об аккумуляторе\n";
            $text .= "/temperature - Датчики температуры\n";
            
            $text .= "\n-- Питание --\n";
            $text .= "/reboot - Перезагрузить ПК\n";
            $text .= "/shutdown - Выключить ПК\n";
            
            $text .= "\n-- Дополнительно --\n";
            $text .= "/uptime - Время работы ПК\n";
            $text .= "/volume - Получить уровень громкости\n";
            $text .= "/volume [0-100|up|+|down|-] - Установить уровень громкости\n";
            $text .= "/brightness - Получить уровень яркости\n";
            $text .= "/brightness [0-100] - Установить уровень яркости\n";
        }
        
        return ['text' => $text, 'keyboard' => $this->getMainKeyboard()];        
    }    
    
    /**
     * Команда /ip
     * Смена/отображение текущей директории 
     */
    public function __ip(){
        $data = json_decode(file_get_contents('http://ipinfo.io/json'), true);
        unset($data['readme']);
        
        $this->send("🌐 IP info: " . json_encode($data, JSON_PRETTY_PRINT));
    }  
    
    public function __osinfo(){
        $info = "Название ОС: " . System::getProperty('os.name') . "\n";
        $info.= "Архитектура JVM:  " . System::getProperty('os.arch') . "\n";
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
    public function __cd($path = null, $noecho = null){
        if(!is_null($path)){
            if($path == '/' || $path == '\\'){
               $this->dir = $path;
            }
            else {
                $cd = realpath($this->dir . '/' . $path);
                if(strlen($cd) > 0) $this->dir = $cd;
                else {
                    $cd = realpath($path);
                    if(strlen($cd) > 0) $this->dir = $cd;
                }
            }
        }
        
        if($noecho == 1) return;
        return ['text' => 'Текущая директория: ' . $this->dir];
    }
    
    /**
     * Команда /ls
     * Отображение содержимого текущей директории 
     */
    public function __ls($path = null){
        if($path !== null){
            $this->__cd($path, 1);
        }
        
        $dirs = [];
        $files = [];
        
        if(is_null($this->dir) || $this->dir == "/" || $this->dir == "\\"){
            $roots = array_map(function($e){ return $e->getAbsolutePath(); }, File::listRoots());
        } else {
            $dirs[] = ['/ls /' => ' 🔙 [/]'];
            $dirs[] = ['/ls ../' => ' 🔙 [../]'];
            $roots = File::of($this->dir)->find();
        }
        
        foreach($roots as $root) {
            $path = realpath($this->dir . '/' . $root);
            $isFile = is_file($path);
            if($isFile){
                $key = "/file \"$root\"";
                $text = "📄 ". $root;
                $files[] = [$key => $text];
            } else {
                $key = "/ls \"$root\"";
                $text = "🗂 ". $root;
                $dirs[] = [$key => $text];
            }
        }
        
        $items = array_merge($dirs, $files);
        $list = "Содержимое директории \"" . $this->dir . "\":";
        
        $rows = [];
        $rowMax = 8;
        
        $cols = [];
        $colMax = 2;
        
        $part = 1;
        
        foreach($items as $k => $item){         
            $cols += $item;
            
            if(sizeof($cols) >= $colMax){
                $rows[] = $cols;
                $cols = [];
            }
                     
            if(sizeof($rows) >= $rowMax){
                $this->send($list, $this->keyboardInline($rows));  
           
                $rows = [];
                $part++;
                $list = "Содержимое директории \"" . $this->dir . "\" [" . $part . "]: ";
                sleep(1 /** $part * 100*/);
            }
        }
    
        if(sizeof($cols) > 0){
            $rows[] = $cols;
        }
        
        if(sizeof($rows) > 0){
            $this->send($list, $this->keyboardInline($rows));        
        }
    }    
    
    protected function getFilePath($file){
        if(file_exists($file)){
            $this->__cd(dirname($file), 1);
            return $file;
        }
        
        $file2 = $this->dir . '/' . $file;
        if(file_exists($file2)) return $file2;
        
        throw new \Exception('Файл "' . $file . '" не найден! [Dir: ' . $this->dir . ']');       
    }
    
    protected function formatBytes(int $bytes){
        if($bytes > 1024 * 1024 * 1024 * 0.9){
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GiB';
        }
        elseif($bytes > 1024 * 1024 * 0.9){
            return round($bytes / (1024 * 1024), 2) . ' MiB';
        }
        elseif($bytes >1024 * 0.9){
            return round($bytes / (1024), 2) . ' KiB';
        }
        
        return $bytes . ' B';
    }
    
    /**
     * Информация о файле
     */    
    public function __file($file = null){
        if($file == 'input://'){
             $this->send('Загружаю файл ... ');
             $file = $this->bot->getLastFile();
             return $this->__file($file->getAbsolutePath());
        }
        
        $file = $this->getFilePath($file);      
             
        $name = basename($file);
        $kb = [];
        
        $key = "/open \"$name\"";
        $kb[] = [$key => '📄 Открыть файл'];
                
        $key = "/download \"$name\"";
        $kb[] = [$key => '🔰 Скачать файл'];
        
        if(Windows::isWin()){
            $key = "/print \"$name\"";
            $kb[] = [$key => '🖨 Распечатать файл'];
        }
        
        $key = "/delete \"$name\"";
        $kb[] = [$key => '🗑 Удалить файл'];
        
        $info = "Файл: $name \n" . 
                "Расположение: " . ( $this->dir ) . "\n" .
                "Размер: " . ( $this->formatBytes(filesize($file)) ) . "\n" /*.
                "Тип: " . filetype($file)*/;
            
        $this->send($info, $this->keyboardInline($kb));
    }
    
    /**
     * Команда /download
     * Отдаёт файл на сксчивание пользователю 
     */    
    public function __download($file = null){  
        $file = $this->getFilePath($file);            
        $this->sendDoc($file);
    }
    
    /**
     * Открыть последний загруженный файл 
     */    
    public function __open($file = null){
        $file = $this->getFilePath($file); 
        $this->send('📄 Открываю файл "' . $file . '".');
        open($file);       
    }     
    
    /**
     * Удалить последний загруженный файл 
     */    
    public function __delete($file = null){
        $file = $this->getFilePath($file);
        $this->send('🗑 Удаляю файл "' . $file . '".');
        unlink($file);       
    }     
    
    /**
     * Распечатать последний загруженный файл 
     */    
    public function __print($file = null){
        $this->checkWin();
        $file = $this->getFilePath($file);
        $res = WindowsScriptHost::PowerShell('
            $word = New-Object -ComObject Word.Application
            $word.visible = $false
            $word.Documents.Open(":file") > $null
            $word.Application.ActiveDocument.printout()
            $word.Application.ActiveDocument.Close()
            $word.quit()
        ', ['file' => $file]);
        $this->send('🖨 Файл "' . $file . '" отправлен на печать. ' . "\n" . $res);
    }
    
    public function __screens(){
        $screens = UXScreen::getScreens();
        $info = "🖥 Список экранов (" . sizeof($screens) . "):\n";
        $keyboard = [];
        
        foreach($screens as $i => $screen){
            $n = $i+1;
            $info .= " #$i. " . $screen->bounds['width'] . "x" . $screen->bounds['height'] . ", позиция: " . $screen->bounds['x'] . "x" . $screen->bounds['y'] . ", DPI: " . $screen->dpi . ".\n";
            $keyboard[] = ["/screenshot__$i" => "🖥 Скриншот экрана №$i (" . $screen->bounds['width'] . "x" . $screen->bounds['height'] . ")"];
        }
        
        $this->send($info, $this->keyboardInline($keyboard));
    }  
    
    /**
     * Команда /screenshot 
     */
    public function __screenshot(int $screenN = 0){
    
        $screens = UXScreen::getScreens();
        if(!isset($screens[$screenN])) return $this->send("Ошибка: экран №$screenN не существует! Укажите номер экрана из спиcка /screens");
        
        $screen = $screens[$screenN];
                
        $this->send("Делаю снимок экрана №$screenN ...");
        $file = File::createTemp('screenshot', '.png');
        Debug::info('Make screenshot to ' . $file->getAbsolutePath());
        
        app()->appModule()->robot->screenshot($screen->bounds, $screen)->save($file);
        
        $this->sendDoc($file->getAbsolutePath());
    }    
        
    /**
     * Команда /cameras
     * Могут вываливаться ошибки, но вроде работает нормально 
     * @param int $camN - Номер камеры в списке камер
     */
    public function __cameras(){
        $cameras = Webcam::getWebcams();
        $keyboard = [];
        if(sizeof($cameras) == 0){
            $list = "📷 Web-камеры не обнаружены";
        } else {
            $list = "📷 Список web-камер (" . sizeof($cameras). "):\n";
            foreach($cameras as $i => $camera){
                $list .= " #$i. " . $camera->name;
                $keyboard[] = ["/photo__$i" => "📷 Снимок с камеры №$i (" . $camera->name . ")"];
            }
        }
          
        $this->send($list, $this->keyboardInline($keyboard));
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
        $this->send('Делаю снимок c камеры №' . $camN . ' (' . $camera->name . ') ...');
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
        $res = "🌡 Температурные датчики: ";
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
    public function __volume($level = null, $noecho = null){
        $this->checkWin();
        
        $kb = $this->keyboardInline([
            [
                '/volume__0__1' => '🔇 0%',
                '/volume__5__1' => '🔈 5%',
                '/volume__10__1' => '🔈 10%',
                '/volume__20__1' => '🔉 20%',
            ],
            [
                '/volume__30__1' => '🔉 30%',
                '/volume__40__1' => '🔉 40%',
                '/volume__50__1' => '🔉 50%',
                '/volume__60__1' => '🔉 60%',
            ],            
            [
                '/volume__70__1' => '🔉 70%',
                '/volume__80__1' => '🔊 80%',
                '/volume__90__1' => '🔊 90%',
                '/volume__100__1' => '🔊 100%',
            ], 
            [
                '/volume__down__1' => '🔈 Volume -',
                '/media' => ' 🎛 Media RC',
                '/volume__up__1' => '🔊 Volume +',
            ],
        ]);
        
        try{
            $ilevel = intval($level);
            
            if($level == 'up' || $level == '+'){
                Windows::pressKey(Windows::VK_VOLUME_UP);
                $answer = "Громкость увеличена";
            } 
            elseif($level == 'down' || $level == '-'){
                Windows::pressKey(Windows::VK_VOLUME_DOWN);
                $answer = "Громкость уменьшена";
            } 
            elseif(is_numeric($level) && $ilevel >= 0 && $ilevel <= 100){
                Windows::setVolumeLevel($ilevel);
                $answer = 'Установлен уровень громкости: ' . $ilevel . '%';
            } else {
                $answer = 'Текущий уровень громкости: ' . Windows::getVolumeLevel() . '%';
            }
        } catch (WindowsException $e){
            $this->send('Ошибка: Управление громкостью недоступно на данном устройстве');
        }
        
        if(strlen($answer) > 0 && $noecho != 1){
            $this->send($answer, $kb);
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
     
    public function __media($noecho = null){
        if($noecho == 1) return;
        $this->checkWin();
        
        $kb = [
            [
                '/key__prev__1' => '⏪ Prev',
                '/key__stop__1' => '⏹ Stop',
                '/key__play__1' => '⏯ Play/Pause',
                '/key__next__1' => '⏩ Next',
            ]
        ];
        
        try {
            $level = Windows::getVolumeLevel();
            $kb[] = [
                '/volume__down__1' => '🔽 Volume -',
                '/volume' => "🔉 $level%",
                '/volume__up__1' => '🔼 Volume +',
            ];
        } catch (WindowsException $e){
             $kb[] = [
                '/volume__down__1' => '🔽 Volume -',
                '/volume' => "🔉 Volume",
                '/volume__up__1' => '🔼 Volume +',
            ];
        }
        
        $this->send("🎛 Media remote control", $this->keyboardInline($kb));
    }   
        
    public function __key($key = null, $noecho = null){
        switch($key){
            case 'next':
                $this->checkWin();
                Windows::pressKey(Windows::VK_MEDIA_NEXT_TRACK);
                break;
                
            case 'prev':
                $this->checkWin();
                Windows::pressKey(Windows::VK_MEDIA_PREV_TRACK);
                break;  
                              
            case 'stop':
                $this->checkWin();
                Windows::pressKey(178);
                break;             
                                 
            case 'play':
                $this->checkWin();
                Windows::pressKey(Windows::VK_MEDIA_PLAY_PAUSE);
                break;
                
            default:
                app()->appModule()->robot->keyPress($key);
        }
        
        if($noecho != 1){
            $this->send('Нажата клавиша "' . $key . '"');
        }
    }

    public function __uptime(){
        $this->checkWin();
        $bootTime = Windows::getUptime(); 
        $programTime = (time() - app()->appModule()->startup) * 1000;
        
        $btime = new Time($bootTime, TimeZone::UTC()); 
        $ptime = new Time($programTime, TimeZone::UTC()); 
        
        $this->send(
            'Компьютер работает: ' . ($btime->day() - 1) . ' дней ' . $btime->hourOfDay() . ' часов ' . $btime->minute() . ' минут ' . $btime->second() . " секунд.\n" .
            'Программа работает: ' . ($ptime->day() - 1) . ' дней ' . $ptime->hourOfDay() . ' часов ' . $ptime->minute() . ' минут ' . $ptime->second() . " секунд." 
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
                "🔋 Состояние аккумулятора: \n" . 
                "Текущий заряд: " . $perc . "%\n" .
                "Напряжение: " . $voltage . "mV\n" .
                "Заряжается: " . ($isCharge ? 'Да': 'Нет') . "\n" .
                "Оставшееся время работы: " . ($time->day() - 1) . ' дней ' . $time->hourOfDay() . ' часов ' . $time->minute() . ' минут ' . $time->second() . ' секунд'
            );
            
        } catch (WindowsException $e){
            $this->send('🔋 Аккумулятор не установлен');
        }
    } 
}