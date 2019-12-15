<?php
namespace main\modules;

use Exception;
use std, gui, framework, main;


class FSO {    

    public $cd;
    
    public function __construct(){
        $this->cd = app()->appModule()->getAppDir();
    }
    
    public function getCurrentDir(){
        return $this->cd;
    }
    
    public function changeDir($path, string $selectBy = 'name'){
        if($selectBy == 'num'){
            $find = $this->getFileByNum($path);
            if($find['type'] == 'file'){
                $this->cd = dirname($find['path']);
            }
            else {
                $this->cd = $find['path'];
            }
            return $this->cd;
        }
        
        if(!is_null($path)){
            if($path == '/' || $path == '\\'){
               $this->cd = '/';
            }
            else {
                $cd = realpath($this->cd . '/' . $path);
                if(strlen($cd) > 0 && is_dir($cd)) $this->cd = $cd;
                else {
                    $cd = realpath($path);
                    if(strlen($cd) > 0 && is_dir($cd)) $this->cd = $cd;
                }
            }
        }
        
        return $this->cd;
    }
    
    public function getFile($path, string $selectBy = 'name'){
        if($selectBy == 'num'){
            $find = $this->getFileByNum($path);
            
            if($find['type'] != 'file'){
                throw new \Exception('Cannot find file #' . $path . ' in path: "' . $this->cd . '" directory found: "' . $find['name'] . '"');
            }
            
            $this->cd = dirname($find['path']);
            return $find;
            
        } else {
            if(file_exists($path)){
                $dir = dirname($path);
                $this->changeDir($dir);
            } elseif(file_exists($this->cd . '/' . $path)){
                $path = realpath($this->cd . '/' . $path);
            } else {
                throw new \Exception('Cannot find file: "' . $path . '" in path: "' . $this->cd. '"');
            }
        }
        
        $filename = basename($path);
        $list = $this->getFileList();
        foreach ($list as $item){
            if($item['name'] == $filename) return $item;
        }
    }
    
    public function getFileByNum(int $num){
        $list = $this->getFileList();
        foreach($list as $item){
            if($item['num'] == $num){
                return $item; 
            }
        }
        
        throw new \Exception('Cannot find file #' . $num . ' in path: ' . $this->cd);    
    }
    
    public function getFileList(?string $fullpath = null){
        $drives = [];
        $dirs = [];
        $files = [];
        
        $fullpath = is_null($fullpath) ? $this->cd : $fullpath;
        
        // Если корень - отгображаем диски
        if(is_null($fullpath) || $fullpath == "/" || $fullpath == "\\") {
            $drives = array_map(function($e){
                static $driveNum = 0; 
                $path = $e->getAbsolutePath();
                
                return ['name' => $path, 'path' => $path, 'type' => 'drive', 'num' => $driveNum++]; 
            }, File::listRoots());
            $roots = [];
        } else {
            $roots = File::of($fullpath)->find();
        }
        
        foreach($roots as $i => $root){
            $path = realpath($fullpath . '/' . $root);
            if(is_file($path)){
                $files[] = [
                    'num' => $i,
                    'path' => $path,
                    'name' => $root,
                    'type' => 'file',
                    'size' => $this->formatBytes(filesize($path))
                ];
            }
            else {
                $dirs[] = [
                    'num' => $i,
                    'path' => $path,
                    'name' => $root,
                    'type' => 'dir'
                ];
            }
        }
        
        return array_merge($drives, $dirs, $files);
    }
    
    public function formatBytes(int $bytes){
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
}