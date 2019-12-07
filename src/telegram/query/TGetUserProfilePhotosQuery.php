<?php


namespace telegram\query;


use telegram\object\TUserProfilePhotos;
use telegram\TelegramBotApi;

class TGetUserProfilePhotosQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'getUserProfilePhotos', false);
    }

    /**
     * @return TGetUserProfilePhotosQuery
     */
    public function user_id($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TGetUserProfilePhotosQuery
     */
    public function offset($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TGetUserProfilePhotosQuery
     */
    public function limit($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TUserProfilePhotos
     */
    function query(){
        return parent::query();
    }
}