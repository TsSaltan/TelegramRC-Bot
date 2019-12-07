<?php


namespace telegram\tools;


use php\lang\IllegalStateException;
use php\lang\Thread;
use php\lang\ThreadPool;
use php\lib\arr;
use php\lib\str;
use php\util\SharedMap;
use php\util\SharedValue;
use telegram\object\TUpdate;
use telegram\TelegramBotApi;

class TUpdateListener{
    /**
     * @var TelegramBotApi
     */
    private $api;

    private $async = false;
    private $threadsCount = 0;
    /**
     * @var SharedValue
     */
    private $onUpdate;

    /**
     * @var ThreadPool
     */
    private $threadPool;
    /**
     * @var Thread
     */
    private $thread;
    private $started = false;


    function __construct(TelegramBotApi $api){
        $this->api = $api;
        $this->onUpdate = new SharedValue([]);
    }
    /**
     * @throws IllegalStateException
     */
    function start(){
        if($this->started){
            throw new IllegalStateException("TUpdateListener already started. Use stop() before");
        }
        $this->started = true;
        $this->threadPool = ($this->threadsCount > 0) ? ThreadPool::createFixed($this->threadsCount) : null;
        if($this->async){
            $this->thread = new Thread([$this, 'listen']);
            $this->thread->start();
        }
        else{
            $this->listen();
        }
    }
    /**
     * @throws IllegalStateException
     */
    function stop(){
        if(!$this->started){
            throw new IllegalStateException("TUpdateListener not started. Use start() before");
        }
        $this->started = false;
        if($this->thread){
            $this->thread->join();
            $this->thread = null;
        }
        if($this->threadPool){
            $this->threadPool->shutdown();
        }
    }
    private function listen(){
        $updateID = 0;

        while($this->started){
            $updates = $this->api->getUpdates()->offset($updateID)->query();
            /** @var TUpdate $last */
            $last = arr::last($updates);
            if($last){
                $updateID = $last->update_id + 1;
            }

            $callbacks = $this->onUpdate->get();
            if($this->threadPool){
                foreach($updates as $update){
                    $this->threadPool->submit(function()use($update, $callbacks){
                        foreach($callbacks as $callback){
                            try{
                                $callback($update);
                            }
                            catch(\Throwable $thr){
                                // oke
                            }
                        }
                    });
                }
            }
            else{
                foreach($updates as $update){
                    foreach($callbacks as $callback){
                        try{
                            $callback($update);
                        }
                        catch(\Throwable $thr){
                            // oke
                        }
                    }
                }
            }
        }
    }
    function getApi() : TelegramBotApi{
        return $this->api;
    }
    function setAsync(bool $value){
        $this->async = $value;
    }
    function getAsync() : bool {
        return $this->async;
    }
    function setThreadsCount(int $value){
        $this->threadsCount = $value;
    }
    function getThreadsCount() : int{
        return $this->threadsCount;
    }
    function addListener(callable $callback){
        $values = $this->onUpdate->get();
        $values[] = $callback;
        $this->onUpdate->set($values);
    }
    function removeListener(callable $callback) : bool{
        $values = $this->onUpdate->get();
        $result = null;

        foreach($values as $key => $value){
            if($value == $callback){
                unset($values[$key]);
                $this->onUpdate->set($values);
                return true;
            }
        }

        return false;
    }
}