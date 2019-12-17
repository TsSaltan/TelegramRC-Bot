<?php
namespace main\modules;

use telegram\object\TMarkup;
use php\desktop\Runtime;
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
     * Callback instance
     * @var int 
     */
    public $cb_instance = -1;
    
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
     * Текущая директория, используется в командах cd, ls, cat, get-file
     * @var FSO 
     */
    public $fso;
    
    public function __construct($chat_id = -1, $username = null, ?TelegramBot $bot = null){
        $this->chat_id = $chat_id;
        $this->username = $username;
        $this->bot = $bot;
        $this->fso = new FSO;
    }
    
    public function setCallbackInstance(int $instance = -1){
        $this->cb_instance = $instance;        
    }
    
    public function isCallback(){
        return $this->cb_instance > 0;
    }
    
    public function alias(string $cmd){
        $cmd = Regex::of('[^\\p{L}\\p{N}\\p{P}\\p{Z}]', Regex::UNICODE_CASE)->with($cmd)->replace('');
        $replace = [
            'Запустить файл' => '/run',
            'Скачать файл' => '/download',
            'Распечатать файл' => '/print',
            'Удалить файл' => '/delete',
            'File Explorer' => '/ls',
        ];
        
        return str_replace(array_keys($replace), array_values($replace), $cmd);
    }
    
    /**
     * Клавиатура над полем ввода 
     */
    protected function keyboard(array $lines){
        return $this->makeKeyboard('keyboard', $lines, ['one_time_keyboard' => false, 'resize_keyboard' => true]);
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
        $keyboard = TMarkup::replyKeyboard();
        $keyboard->button(SMILE_HELP . ' Help')->button(SMILE_PC . ' OSInfo')->button(SMILE_NETWORK . ' IP info');
        $keyboard->row();
        
        $keyboard->button(SMILE_DISPLAY . ' Screens')->button(SMILE_CAMERA . ' Cameras')->button(SMILE_FOLDER . ' File Explorer');
        
         if($isWin){
            $keyboard->row()
                     ->button(SMILE_MEDIA . ' Media RC')->button(SMILE_SOUND_50 . ' Volume')->button(SMILE_BRIGHT_50 . ' Brightness')
                     ->row()
                     ->button(SMILE_BATTERY . ' Battery')->button(SMILE_ARROW_REFRESH . ' Reboot')->button(SMILE_DOT_RED . ' Shutdown');
        }
        
        return $keyboard;       
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
     * Отправить ответ на нажатие кнопки 
     */
    public function sendCallback($text){
        $this->bot->sendAnswer($this->chat_id, ['text' => $text, 'callback' => $this->cb_instance]);
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
     * Команда при получении файла. После его загрузки.
     * @param array $input                 
     *    'type' => 'photo|document',
     *    'file_name' => 'photo',
     *    'mime_type' => 'image/jpeg',
     *    'file_id' =>
     *    'file_size' =>  
     */      
    public function inputFileMsg(File $file, array $input){
        $this->send(SMILE_DOWNLOAD . ' Получен файл: ' . $input['file_name']);
        $this->__file($file->getAbsolutePath());
    }
    
    /**
     * Команда /start 
     * Приветствие при запуске бота
     */
    public function __start(){  
        return ['text' => SMILE_BOT . ' Вас приветствует бот для удалённого управления компьютером. Введите /help для получения справки.', 'keyboard' => $this->getMainKeyboard()];        
    }
    
    /**
     * Команда /help
     * Справка / помощь
     */
    public function __help(){
        $isWin = Windows::isWin();

        $text = SMILE_BOT . " Версия бота: " . AppModule::APP_VERSION . " \n";

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
        $text .= "/photo [n] - Сделать фото с выбравнной из списка камеры\n";
        
        $text .= "\n-- Клавиатура --\n";   
        $text .= "/key \"код любой кнопки (ENTER, SPACE, etc...)\" - Нажать кнопку\n";  
        if($isWin){
            $text .= "/key__play - Воспроизведение / пауза\n";
            $text .= "/key__stop - Остановить воспроизведение\n";
            $text .= "/key__next - Следующий трек\n";
            $text .= "/key__prev - Предыдущий трек\n";
            $text .= "/media - Клавиатура с медиа кнопками\n";
            
            $text .= "\n-- Железо --\n";
            //$text .= "/hardware - Железо компьютера\n";
            $text .= "/ram - Оперативная память\n";
            $text .= "/battery - Информация об аккумуляторе\n";
            $text .= "/temperature - Датчики температуры\n";
            
            $text .= "\n-- Питание --\n";
            $text .= "/reboot - Перезагрузить ПК\n";
            $text .= "/shutdown - Выключить ПК\n";
        }            
        
        $text .= "\n-- Дополнительно --\n";
        $text .= "/uptime - Время работы\n";
        
        if($isWin){
            $text .= "/volume - Управление громкостью\n";
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
        
        $info = SMILE_NETWORK . " IP info: \n";
        foreach ($data as $k => $v){
            $info .= "$k: $v\n";    
        }
        
        $this->send($info);
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
    public function __cd($path = null){                
        return ['text' => 'Текущая директория: ' . $this->fso->changeDir($path)];
    }
    
    /**
     * Команда /ls
     * Отображение содержимого текущей директории 
     * @param string $path Путь. / - корень, для отображения дисков
     * @param string $selectBy name|num Ищет файл или по его имени (если name) или по порядковому номеру (если num - используется, когда есть лимит на длину сообщения в telegram)
     */
    public function __ls($path = null, string $selectBy = 'name'){
        if(strlen($path) > 0){
            $this->fso->changeDir($path, $selectBy);
        }
        
        $btn[] = [SMILE_ARROW_UP . ' ls / ', SMILE_UP . ' ls ../ '];
        $btn[] = [SMILE_HELP . ' Help'];
        
        $items = $this->fso->getFileList();
        
        $message = "Содержимое директории \"" . $this->fso->getCurrentDir() . "\"\n\n";
        foreach($items as $item){    
            if(strlen($item['name']) > 30){
                $name = substr($item['name'], 0, 13) . ' ... ' . substr($item['name'], -12);
            } else {
                $name = $item['name'];
            }
            
            switch($item['type']){
                case 'drive':
                    $line = SMILE_DISC . ' ' . $name . " | /ls__" . $item['num'] . "__num \n";
                    break;
                    
                case 'dir':
                    $line = SMILE_FOLDER . ' ' . $name . " | /ls__" . $item['num'] . "__num \n";
                    break;
                    
                case 'file':
                    $line = SMILE_FILE . ' ' . $name . " | /file__" . $item['num'] . "__num \n";
                    break;
            }
            
            if(strlen($message . $line) > TelegramBot::MAX_MESSAGE_LENGTH){
                $this->send($message, $this->keyboard($btn)); 
                $message = '';
            }
            $message .= $line;
        }
        
        if(strlen($message) > 0){
            $this->send($message, $this->keyboard($btn)); 
        }
    }    
    
   
    /**
     * Информация о файле
     */    
    public function __file($file = null, string $selectBy = 'name'){
        $file = $this->fso->getFile($file, $selectBy);      
             
        $name = $file['name'];
        if(strlen($name) > 20){
            $cmd = $file['num'] . " num";
        }
        else {
            $cmd = $file['name'];
        }
        
        $kb = [];
        
        $kb[0][] = SMILE_PC . ' Запустить файл ' . $cmd;
        $kb[0][] = SMILE_DOWNLOAD . ' Скачать файл ' . $cmd;
        
        if(Windows::isWin()){
            $kb[1][] = SMILE_PRINT . ' Распечатать файл ' . $cmd;
        }
        
        $kb[1][] = SMILE_TRASH . ' Удалить файл ' . $cmd;
        $kb[2][] = SMILE_FOLDER . ' File Explorer';
        $kb[2][] = SMILE_HELP . ' Help';
        
        $info = SMILE_FILE . " Имя файла: $name \n" . 
                "Путь: " . dirname($file['path']) . "\n" .
                "Размер: " . $file['size'] . "\n";
            
//        $this->send($info, $this->keyboardInline($kb));
        $this->send($info, $this->keyboard($kb));
    }
    
    /**
     * @command /download
     * Отдаёт файл на сксчивание пользователю 
     */    
    public function __download($file = null, string $selectBy = 'name'){  
        $file = $this->fso->getFile($file, $selectBy);          
        $this->sendDoc($file['path']);
    }
    
    /**
     * /run "file"
     * Запустить файл 
     */    
    public function __run($file = null, string $selectBy = 'name'){
        $file = $this->fso->getFile($file, $selectBy); 
        $this->send(SMILE_PC . ' Запускаю файл "' . $file['name'] . '".');
        open($file['path']);       
    }     
    
    /**
     * /delete "file"
     * Удалить файл 
     */    
    public function __delete($file = null, string $selectBy = 'name'){
        $file = $this->fso->getFile($file, $selectBy); 
        $kb = [
            [SMILE_FOLDER . ' File Explorer'],
            [SMILE_HELP . ' Help'],
        ];
        $kb = $this->keyboard($kb);
        
        if(File::of($file['path'])->delete()){
            $this->send(SMILE_TRASH . ' Файл удалён "' . $file['name'] . '"', $kb);
        } else {
            $this->send(SMILE_TRASH . ' Не удалось удалить файл "' . $file['name'] . '"', $kb);
        }
    }     
    
    /**
     * /print file
     * Отправить файл на печать
     */    
    public function __print($file = null, string $selectBy = 'name'){
        $this->checkWin();
        $file = $this->fso->getFile($file, $selectBy); 
        $res = WindowsScriptHost::PowerShell('
            $word = New-Object -ComObject Word.Application
            $word.visible = $false
            $word.Documents.Open(":file") > $null
            $word.Application.ActiveDocument.printout()
            $word.Application.ActiveDocument.Close()
            $word.quit()
        ', ['file' => $file['path']]);
        $this->send(SMILE_PRINT . ' Файл "' . $file['name'] . '" отправлен на печать. ' . "\n" . $res);
    }
    
    /**
     * /screens
     */
    public function __screens(){
        $screens = UXScreen::getScreens();
        $info = SMILE_DISPLAY . " Список экранов (" . sizeof($screens) . "):\n";
        $keyboard = [];
        
        foreach($screens as $i => $screen){
            $n = $i+1;
            $info .= " #$i. " . $screen->bounds['width'] . "x" . $screen->bounds['height'] . ", позиция: " . $screen->bounds['x'] . "x" . $screen->bounds['y'] . ", DPI: " . $screen->dpi . ".\n";
            $keyboard[] = ["/screenshot__$i" => SMILE_DISPLAY . " Скриншот экрана №$i (" . $screen->bounds['width'] . "x" . $screen->bounds['height'] . ")"];
        }
        
        $this->send($info, $this->keyboardInline($keyboard));
    }  
    
    /**
     * /screenshot screen_num
     */
    public function __screenshot(int $screenN = 0){
    
        $screens = UXScreen::getScreens();
        if(!isset($screens[$screenN])) return $this->send("Ошибка: экран №$screenN не существует! Укажите номер экрана из спиcка /screens");
        
        $screen = $screens[$screenN];
        
        $msg = "Делаю снимок экрана №$screenN ...";
        if($this->isCallback()){
            $this->sendCallback($msg);
        } else {
            $this->send($msg);
        }
        
        $file = File::of(app()->appModule()->getAppDownloadDir() . '/' . time() . '_screenshot_' . $screenN . '.png');
        $file->createNewFile(); 
  
        Debug::info('Make screenshot to ' . $file->getAbsolutePath());
        
        app()->appModule()->robot->screenshot($screen->bounds, $screen)->save($file);
        
        $this->sendDoc($file->getAbsolutePath());
    }    
        
    /**
     * Команда /cameras
     * Могут вываливаться ошибки, но вроде работает нормально 
     */
    public function __cameras(){
        $cameras = Webcam::getWebcams();
        $keyboard = [];
        if(sizeof($cameras) == 0){
            $list = SMILE_CAMERA . " Web-камеры не обнаружены";
        } else {
            $list = SMILE_CAMERA . " Список web-камер (" . sizeof($cameras). "):\n";
            foreach($cameras as $i => $camera){
                $list .= " #$i. " . $camera->name;
                $keyboard[] = ["/photo__$i" => SMILE_CAMERA . " Снимок с камеры №$i (" . $camera->name . ")"];
            }
        }
          
        $this->send($list, $this->keyboardInline($keyboard));
    }   
           
    /**
     * Команда /photo camera_num
     * Могут вываливаться ошибки, но вроде работает нормально 
     * @param int $camN - Номер камеры в списке камер
     */
    public function __photo(int $camN = 0){
        $cameras = Webcam::getWebcams();
        if(!isset($cameras[$camN])) return $this->send('Указана несуществующая камера. Список камер доступен по команде /cameras');
        $camera = $cameras[$camN];
        
        $file = File::of(app()->appModule()->getAppDownloadDir() . '/' . time() . '_shot_' . $camN . '.png');
        $file->createNewFile(); 
        
        $msg = 'Делаю снимок c камеры №' . $camN . ' (' . $camera->name . ') ...';
        if($this->isCallback()){
            $this->sendCallback($msg);
        } 
        else {
            $this->send($msg);
        }
        
        $camera->open();
        $camera->getImage()->save($file);
        $camera->close();
        
        $this->sendDoc($file->getAbsolutePath());    
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
        $jfree = Runtime::freeMemory();
        $jtotal = Runtime::totalMemory();
        
        
        $message = SMILE_BOT . ' TelegramRC Bot used ' . $this->fso->formatBytes($jtotal - $jfree) . ' of ' . $this->fso->formatBytes($jtotal) . ' (' . round($jfree / $jtotal * 100) . '%)';
        
        if(Windows::isWin()){   
            $total = Windows::getTotalRAM();
            $free = Windows::getFreeRAM();  
            $message .= "\n" . SMILE_DISC . " Total RAM: " . $this->fso->formatBytes($total);
            $message .= "\nUsed RAM: ". $this->fso->formatBytes($total-$free) . " (" .  round(($total-$free) / $total * 100) . "%)";
            $message .= "\nFree RAM: ". $this->fso->formatBytes($free);
        }
                
        $this->send($message);
    }
        
    /**
     * Команда /volume [0-100] 
     * Получить или изменить уровень громкости
     * Только для Windows
     */
    public function __volume($level = null){
        $this->checkWin();
        $showKb = false;
        
        $kb = $this->keyboardInline([
            [
                '/volume__0__1' => SMILE_SOUND_0 . ' 0%',
                '/volume__5__1' => SMILE_SOUND_25. ' 5%',
                '/volume__10__1' => SMILE_SOUND_25. ' 10%',
                '/volume__20__1' => SMILE_SOUND_50. ' 20%',
            ],
            [
                '/volume__30__1' => SMILE_SOUND_50. ' 30%',
                '/volume__40__1' => SMILE_SOUND_50. ' 40%',
                '/volume__50__1' => SMILE_SOUND_50. ' 50%',
                '/volume__60__1' => SMILE_SOUND_50. ' 60%',
            ],            
            [
                '/volume__70__1' => SMILE_SOUND_50. ' 70%',
                '/volume__80__1' => SMILE_SOUND_100. ' 80%',
                '/volume__90__1' => SMILE_SOUND_100. ' 90%',
                '/volume__100__1' => SMILE_SOUND_100. ' 100%',
            ], 
            [
                '/volume__down__1' => SMILE_SOUND_25 . ' Volume -',
                '/media' => SMILE_MEDIA . ' Media RC',
                '/volume__up__1' => SMILE_SOUND_100 . ' Volume +',
            ],
        ]);
        
        try{
            $ilevel = intval($level);
            
            if($level == 'up' || $level == '+'){
                Windows::pressKey(Windows::VK_VOLUME_UP);
                $answer = "Volume up";
            } 
            elseif($level == 'down' || $level == '-'){
                Windows::pressKey(Windows::VK_VOLUME_DOWN);
                $answer = "Volume down";
            } 
            elseif(is_numeric($level) && $ilevel >= 0 && $ilevel <= 100){
                Windows::setVolumeLevel($ilevel);
                $answer = 'Volume level: ' . $ilevel . '%';
            } else {
                $showKb = true;
                $level = Windows::getVolumeLevel();
                $answer = 'Volume level: ' . $level . '%';
            }
        } catch (WindowsException $e){
           $answer = 'Error: Cannot control volume level';
        }
        
        if($this->isCallback() && !$showKb){
            $this->sendCallback(SMILE_SOUND_50 . ' ' . $answer);
        }
        else {
            $this->send(SMILE_SOUND_50 . ' ' . $answer, $kb);
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
        
        $kb = [
            [
                '/key__prev__1' => SMILE_MEDIA_PREV . ' Prev',
                '/key__stop__1' => SMILE_MEDIA_STOP . ' Stop',
                '/key__play__1' => SMILE_MEDIA_PLAY . ' Play/Pause',
                '/key__next__1' => SMILE_MEDIA_NEXT . ' Next',
            ]
        ];
        
        try {
            $level = Windows::getVolumeLevel();
            $kb[] = [
                '/volume__down__1' => SMILE_SYMBOL_DOWN . ' Volume -',
                '/volume' => SMILE_SOUND_50 . " $level%",
                '/volume__up__1' => SMILE_SYMBOL_UP . ' Volume +',
            ];
        } catch (WindowsException $e){
             $kb[] = [
                '/volume__down__1' => SMILE_SYMBOL_DOWN . ' Volume -',
                '/volume__up__1' => SMILE_SYMBOL_UP . ' Volume +',
            ];
        }
        
        $this->send(SMILE_MEDIA . " Media remote control", $this->keyboardInline($kb));
    }   
        
    public function __key($key = null){
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
            case 'pause':
                $this->checkWin();
                Windows::pressKey(Windows::VK_MEDIA_PLAY_PAUSE);
                break;
                
            default:
                app()->appModule()->robot->keyPress($key);
        }
        
        if($this->isCallback()){
            $this->sendCallback(SMILE_MEDIA . " Pressed: " . $key);
        } else {
            $this->send(SMILE_MEDIA . " Pressed: " . $key);
        }
    }

    public function __uptime(){
        $programTime = (time() - app()->appModule()->startup) * 1000;
        $ptime = new Time($programTime, TimeZone::UTC()); 
        $message = SMILE_CLOCK . ' Программа работает: ' . ($ptime->day() - 1) . ' дней ' . $ptime->hourOfDay() . ' часов ' . $ptime->minute() . ' минут ' . $ptime->second() . " секунд.";

        
        if(Windows::isWin()){
            $bootTime = Windows::getUptime(); 
            $btime = new Time($bootTime, TimeZone::UTC());        
            $message .= "\n" . SMILE_PC . " Компьютер работает: " . ($btime->day() - 1) . ' дней ' . $btime->hourOfDay() . ' часов ' . $btime->minute() . ' минут ' . $btime->second() . " секунд.";
        }        
        
        $this->send($message);
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
                SMILE_BATTERY . " Состояние аккумулятора: \n" . 
                "Текущий заряд: " . $perc . "%\n" .
                "Напряжение: " . $voltage . "mV\n" .
                "Заряжается: " . ($isCharge ? 'Да': 'Нет') . "\n" .
                "Оставшееся время работы: " . ($time->day() - 1) . ' дней ' . $time->hourOfDay() . ' часов ' . $time->minute() . ' минут ' . $time->second() . ' секунд'
            );
            
        } catch (WindowsException $e){
            $this->send(SMILE_BATTERY . ' Аккумулятор не установлен');
        }
    } 
}