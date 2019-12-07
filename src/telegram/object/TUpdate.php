<?php


namespace telegram\object;


class TUpdate
{
    /**
     * @var int
     */
    public $update_id;
    /**
     * @var TMessage
     */
    public $message;
    public $inline_query;
    public $chosen_inline_result;
    public $callback_query;
}