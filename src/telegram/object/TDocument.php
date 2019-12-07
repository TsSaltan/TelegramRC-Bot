<?php


namespace telegram\object;


use php\lib\str;

class TDocument
{
    /**
     * @var string
     */
    public $file_id;
    /**
     * @var TPhotoSize
     */
    public $thumb;
    /**
     * @var str
     */
    public $file_name;
    /**
     * @var string
     */
    public $mime_type;
    /**
     * @var int
     */
    public $file_size;
}