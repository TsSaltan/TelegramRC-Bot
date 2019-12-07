<?php


namespace telegram\object;


use php\lib\str;

class TChat
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     * private, group, supergroup, channel
     */
    public $type;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $username;
    /**
     * @var string
     */
    public $first_name;
    /**
     * @var string
     */
    public $last_name;
    /**
     * @var bool
     */
    public $all_members_are_administrators;
}