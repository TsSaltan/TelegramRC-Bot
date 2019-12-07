<?php


namespace telegram\query;


use php\lib\str;
use telegram\object\TFile;
use telegram\TelegramBotApi;

class TGetFileQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'getFile', false);
    }
    /**
     * @return TGetFileQuery
     */
    public function file_id($value){
        return $this->put(__FUNCTION__, (string)$value);
    }

    /**
     * @return TFile
     */
    function query(){
        $result = parent::query();
        $result->download_url = str::format('https://api.telegram.org/file/bot%s/%s', $this->getApi()->getToken(), $result->file_path);
        return $result;
    }
}