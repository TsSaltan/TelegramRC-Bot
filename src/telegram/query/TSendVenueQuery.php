<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendVenueQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendVenue', false);
    }
    /**
     * @return TSendVenueQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVenueQuery
     */
    public function latitude($value){
        return $this->put(__FUNCTION__, (float)$value);
    }
    /**
     * @return TSendVenueQuery
     */
    public function longitude($value){
        return $this->put(__FUNCTION__, (float)$value);
    }
    /**
     * @return TSendVenueQuery
     */
    public function title($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TSendVenueQuery
     */
    public function address($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TSendVenueQuery
     */
    public function foursquare_id($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TSendVenueQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, (bool)$value);
    }
    /**
     * @return TSendVenueQuery
     */
    public function reply_to_message_id($value){
        return $this->put(__FUNCTION__, (bool)$value);
    }
    /**
     * @return TMessage
     */
    public function query(){
        return parent::query();
    }
}