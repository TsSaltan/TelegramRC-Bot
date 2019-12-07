<?php


namespace telegram\object;


class TSticker
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
}