<?php


namespace telegram;


use php\format\JsonProcessor;
use php\format\ProcessorException;
use php\io\File;
use php\io\IOException;
use php\io\MemoryStream;
use php\io\Stream;
use php\lib\fs;
use php\lib\str;
use php\net\Proxy;
use php\net\URL;
use php\net\URLConnection;
use telegram\exception\TelegramError;
use telegram\exception\TelegramException;
use telegram\query\TForwardMessageQuery;
use telegram\query\TGetFileQuery;
use telegram\query\TGetMeQuery;
use telegram\query\TGetUpdatesQuery;
use telegram\query\TGetUserProfilePhotosQuery;
use telegram\query\TKickChatMemberQuery;
use telegram\query\TSendAudioQuery;
use telegram\query\TSendChatActionQuery;
use telegram\query\TSendContactQuery;
use telegram\query\TSendDocumentQuery;
use telegram\query\TSendLocationQuery;
use telegram\query\TSendMessageQuery;
use telegram\query\TSendPhotoQuery;
use telegram\query\TSendStickerQuery;
use telegram\query\TSendVenueQuery;
use telegram\query\TSendVideoQuery;
use telegram\query\TSendVoiceQuery;
use telegram\query\TUnbanChatMemberQuery;

class TelegramBotApi{
    private $baseURL = 'https://api.telegram.org/bot%s/%s';
    private $token;
    /**
     * @var Proxy
     */
    private $proxy;

    /**
     * @var JsonProcessor
     */
    private $json;


    function __construct(?string $token = null){
        $this->token = $token;
        $this->json = new JsonProcessor;
    }
    /**
     * @return TGetMeQuery
     */
    function getMe(){
        return new TGetMeQuery($this);
    }
    /**
     * @return TSendMessageQuery
     */
    function sendMessage(){
        return new TSendMessageQuery($this);
    }
    /**
     * @return TForwardMessageQuery
     */
    function forwardMessage(){
        return new TForwardMessageQuery($this);
    }
    /**
     * @return TSendPhotoQuery
     */
    function sendPhoto(){
        return new TSendPhotoQuery($this);
    }
    /**
     * @return TSendAudioQuery
     */
    function sendAudio(){
        return new TSendAudioQuery($this);
    }
    /**
     * @return TSendDocumentQuery
     */
    function sendDocument(){
        return new TSendDocumentQuery($this);
    }
    /**
     * @return TSendStickerQuery
     */
    function sendSticker(){
        return new TSendStickerQuery($this);
    }
    /**
     * @return TSendVideoQuery
     */
    function sendVideo(){
        return new TSendVideoQuery($this);
    }
    /**
     * @return TSendVoiceQuery
     */
    function sendVoice(){
        return new TSendVoiceQuery($this);
    }
    /**
     * @return TSendLocationQuery
     */
    function sendLocation(){
        return new TSendLocationQuery($this);
    }
    /**
     * @return TSendVenueQuery
     */
    function sendVenue(){
        return new TSendVenueQuery($this);
    }
    /**
     * @return TSendContactQuery
     */
    function sendContact(){
        return new TSendContactQuery($this);
    }
    /**
     * @return TSendChatActionQuery
     */
    function sendChatAction(){
        return new TSendChatActionQuery($this);
    }
    /**
     * @return TGetUserProfilePhotosQuery
     */
    function getUserProfilePhotos(){
        return new TGetUserProfilePhotosQuery($this);
    }
    /**
     * @return TGetFileQuery
     */
    function getFile(){
        return new TGetFileQuery($this);
    }
    /**
     * @return TKickChatMemberQuery
     */
    function kickChatMember(){
        return new TKickChatMemberQuery($this);
    }
    /**
     * @return TUnbanChatMemberQuery
     */
    function unbanChatMember(){
        return new TUnbanChatMemberQuery($this);
    }
    /**
     * @return TGetUpdatesQuery
     */
    function getUpdates(){
        return new TGetUpdatesQuery($this);
    }


    function setProxy(?Proxy $proxy){
        $this->proxy = $proxy;
    }
    function getProxy() : ?Proxy{
        return $this->proxy;
    }
    function setToken(?string $token){
        $this->token = $token;
    }
    function getToken() : ?string{
        return $this->token;
    }
    /**
     * @param $method
     * @param $args
     * @param bool $multipart
     * @return mixed
     * @throws TelegramException
     * @throws TelegramError
     */
    function query($method, array $args = [], bool $multipart = false){
        try{
            $boundary = $multipart ? str::random() : null;
            $connection = $this->createConnection($method, $multipart, $boundary);
            $connection->useCaches = false;
            $connection->connect();
            if($multipart){
                fs::copy($this->formatMultipart($args, $boundary), $connection->getOutputStream());
            }
            else{
                $connection->getOutputStream()->write($this->json->format($args));
            }
            if($connection->responseCode != 200){
                throw new TelegramException("Server response invalid status code {$connection->responseCode}");
            }
            $rawResponse = $connection->getInputStream()->readFully();
            $connection->disconnect();
            $response = $this->json->parse($rawResponse);
            if(!$response->ok){
                throw new TelegramError($response->error_code, $response->description);
            }
            return $response->result;
        }
        catch(IOException $e){
            throw new TelegramException("Connection error", $e);
        }
        catch(ProcessorException $e){
            throw new TelegramException("Parse error", $e);
        }
    }
    /**
     * @throws IOException
     */
    private function formatMultipart(array $args, string $boundary){
        $stream = new MemoryStream();


        foreach($args as $name => $value){
            $stream->write("--{$boundary}\r\n");
            $isFile = $value instanceof File;
            $type = $isFile ? URLConnection::guessContentTypeFromName($value->getName()) : 'text/plain';

            if($isFile){
                $stream->write("Content-Disposition: form-data; name=\"{$name}\";filename=\"{$value->getName()}\"\r\n");
                $stream->write("Content-Type: {$type}\r\n");
                $stream->write("Content-Transfer-Encoding: binary\r\n");
            }
            else{
                $stream->write("Content-Disposition: form-data; name=\"{$name}\"\r\n");
                $stream->write("Content-Type: {$type}\r\n");
            }
            $stream->write("\r\n");

            $stream->write($isFile ? fs::get($value) : $value);
            $stream->write("\r\n");
        }
        $stream->write("--{$boundary}--\r\n");
        $stream->seek(0);

        return $stream;
    }
    /**
     * @param string $method
     * @param bool $multipart
     * @param string $boundary
     * @return URLConnection
     */
    private function createConnection(string $method, bool $multipart = false, ?string $boundary = null){
        $connection = URLConnection::create(str::format($this->baseURL, $this->token, $method), $this->proxy);
        $connection->doInput = true;
        $connection->doOutput = true;
        $connection->requestMethod = 'POST';
        $connection->setRequestProperty('Content-Type', $multipart ? "multipart/form-data; boundary=\"{$boundary}\"" : 'application/json');

        return $connection;
    }
}