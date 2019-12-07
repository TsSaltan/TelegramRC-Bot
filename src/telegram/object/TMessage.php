<?php


namespace telegram\object;


use php\lib\str;

class TMessage
{
    /**
     * @var int
     */
    public $message_id;
    /**
     * @var TUser
     */
    public $from;
    /**
     * @var int
     */
    public $date;
    /**
     * @var TChat
     */
    public $chat;
    /**
     * @var TUser
     */
    public $forward_from;
    /**
     * @var int
     */
    public $forward_date;
    /**
     * @var TMessage
     */
    public $reply_to_message;
    /**
     * @var string
     */
    public $text;
    /**
     * @var TMessageEntity[]
     */
    public $entities;
    /**
     * @var TAudio
     */
    public $audio;
    /**
     * @var TDocument
     */
    public $document;
    /**
     * @var TPhotoSize[]
     */
    public $photo;
    /**
     * @var TSticker
     */
    public $sticker;
    /**
     * @var TVideo
     */
    public $video;
    /**
     * @var TVoice
     */
    public $voice;
    /**
     * @var string
     */
    public $caption;
    /**
     * @var TContact
     */
    public $contact;
    /**
     * @var TLocation
     */
    public $location;
    /**
     * @var TVenue
     */
    public $venue;
    /**
     * @var TUser
     */
    public $new_chat_member;
    /**
     * @var TUser
     */
    public $left_chat_member;
    /**
     * @var string
     */
    public $new_chat_title;
    /**
     * @var TPhotoSize[]
     */
    public $new_chat_photo;
    /**
     * @var bool
     */
    public $delete_chat_photo;
    /**
     * @var bool
     */
    public $group_chat_created;
    /**
     * @var bool
     */
    public $supergroup_chat_created;
    /**
     * @var bool
     */
    public $channel_chat_created;
    /**
     * @var int
     */
    public $migrate_to_chat_id;
    /**
     * @var int
     */
    public $migrate_from_chat_id;
    /**
     * @var TMessage
     */
    public $pinned_message;
}