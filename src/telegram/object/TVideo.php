<?php


namespace telegram\object;


class TVideo
{
    /**
     * @var string
     */
    public $file_id;
    /**
     * @var int
     */
    public $width;
    /**
     * @var int
     */
    public $height;
    /**
     * @var int
     */
    public $file_size;
    /**
     * @var TPhotoSize
     */
    public $thumb;
    /**
     * @var int
     */
    public $duration;
    /**
     * @var string
     */
    public $mime_type;
}