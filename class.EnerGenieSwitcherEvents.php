<?php
/**
 * Created by PhpStorm.
 * User: Ashus
 */

class EnerGenieSwitcherEvents {
    /** @var array|EnerGenieSwitcherEvent[] */
    private $events = [];

    public function addEvent(EnerGenieSwitcherEvent $event) {
        $this->events[] = $event;
    }

    /**
     * @return string
     */
    public function encodeData() {
        $encoded = $this->encodeInt(time());

        foreach ($this->events as $event) {
            $flags = $this->createFlags($event->state);
            $encoded .= bin2hex($flags) . $this->encodeInt($event->time);
        }

        $encoded .= "e5" . $this->encodeInt(0);
        return $encoded;
    }

    /**
     * @param bool $state
     * @return string
     */
    private function createFlags($state) {
        $flags = str_pad($state, 2, '0', STR_PAD_LEFT);
        // TODO implement periodics... + ($event['periodic'] * 2);
        return hex2bin($flags);
    }

    /**
     * @param int $d
     * @return string
     */
    private function encodeInt($d) {
        $d = dechex($d);
        $d = str_pad($d, 8, '0', STR_PAD_LEFT);
        $a = str_split($d, 2);
        $a = array_reverse($a);
        $a = implode('', $a);
        return $a;
    }
}