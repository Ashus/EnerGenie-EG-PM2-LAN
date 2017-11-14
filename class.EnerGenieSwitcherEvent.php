<?php
/**
 * Created by PhpStorm.
 * User: Ashus
 */

class EnerGenieSwitcherEvent {
    /** @var bool */
    public $state;
    /** @var int */
    public $time;

    /**
     * EnerGenieSwitcherEvent constructor.
     * @param int $time
     * @param bool $state
     */
    public function __construct($time, $state) {
        $this->time = $time;
        $this->state = $state;
    }
}