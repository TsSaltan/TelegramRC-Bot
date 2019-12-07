<?php
namespace main\modules;

use std, gui, framework, main;

/**
 * Здесь будут храниться настройки 
 */
class Config extends AbstractModule {
    
    public static $cfgFile = 'config.json';
    
    /**
     * @var array 
     */
    protected static $data = [];
    
    /**
     * Загрузка настроек из файла в кеш 
     */
    public static function load(){
        if(self::isFileExists()){
            self::$data = json_decode(file_get_contents(self::$cfgFile), true);        
        }
    }
    
    /**
     * Запись всего в файл 
     */
    public static function save(){
        file_put_contents(self::$cfgFile, json_encode(self::$data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Получить значение 
     */
    public static function get($key){
        return self::$data[$key] ?? null;
    }
    
    /**
     * Установить значение 
     */
    public static function set($key, $value){
        self::$data[$key] = $value;
        self::save();
    }
    
    /**
     * Если нам неизвестен token, то необходима процедура установки 
     */
    public static function isInstallRequired(): bool {
        return !isset(self::$data['token']);
    }
    
    /**
     * Существует ли файл с настройками 
     */
    public static function isFileExists(): bool {
        return file_exists(self::$cfgFile);
    }
}