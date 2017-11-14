<?php

require_once __DIR__ . '/class.EnerGenieSwitcherEvent.php';
require_once __DIR__ . '/class.EnerGenieSwitcherEvents.php';

/**
 * Class EnerGenieSwitcher
 * @author Florian Arndt <post@florianarndt.com>
 * @author Ulf Haase <ulf.haase@uhsb.de>
 * @author Ashus <https://ashus.ashus.net>
 * @package EnerGenie
 * @subpackage
 * @copyright https://github.com/flowli/EnerGenie-EG-PM2-LAN
 * @version 2.0
 */
class EnerGenieSwitcher {
    const REQUEST_TIMEOUT = 1000;
    const RESTART_DELAY_OFF = 5;
    const RESTART_DELAY_ON = 5;

    /** @var string */
    private $ip;
    /** @var string */
    private $password;
    /** @var bool */
    private $debug;

    /**
     * Check prerequisites and set-up
     * @param string $ip
     * @param string $password
     * @param bool $debug
     * @throws Exception
     */
    public function __construct($ip, $password, $debug = false) {
        if (!extension_loaded('curl')) {
            throw new Exception('CURL extension required');
        }
        $this->ip = $ip;
        $this->password = $password;
        $this->debug = $debug;
    }

    public function doLogout() {
        $html = $this->postRequest('http://' . $this->ip . '/login.html', array('pw' => ''));
        if (strstr($html, "EnerGenie Web:"))
            $result = TRUE;
        else
            $result = FALSE;

        if ($this->debug) {
            if ($result)
                echo "Logout " . $this->ip . ": successful!!!<br>\n";
            else
                echo "Logout " . $this->ip . "-->" . $html . "<--: failed!!!<br>\n";
        }

        return $result;
    }

    public function doLogin() {
        $html = $this->postRequest('http://' . $this->ip . '/login.html', array('pw' => $this->password));

        if ($html == "" OR strstr($html, "EnerGenie Web:"))
            $result = FALSE;
        else
            $result = TRUE;

        if ($this->debug) {
            if ($result)
                echo "Login " . $this->ip . ": successful!!!<br>\n";
            else
                echo "Login " . $this->ip . ": failed!!!<br>\n";
        }

        return $result;
    }

    /**
     * Get status of all sockets, returns array of bool ON/OFF statuses or false on error
     * @return array|bool[]|false
     */
    public function getSockets() {
        if (!$this->doLogin())
            return false;

        $html = $this->getRequest('http://' . $this->ip . '/energenie.html');
        preg_match_all('/var sockstates \= \[([0-1],[0,1],[0,1],[0,1])\]/', $html, $matches);
        if (!isset($matches[1][0])) {
            return false;
        }
        $states = explode(',', $matches[1][0]);
        $this->doLogout();

        return [
            1 => $states[0] == '1',
            2 => $states[1] == '1',
            3 => $states[2] == '1',
            4 => $states[3] == '1'
        ];
    }

    /**
     * Get individual socket status, returns bool ON/OFF or null on error
     * @param int $socket
     * @return bool|null
     */
    public function getSocket($socket) {
        $statuses = $this->getSockets();
        if ($statuses && isset($statuses[$socket])) {
            return $statuses[$socket];
        }
        return null;
    }

    /**
     * Sets statuses of multiple sockets at once, input is an assoc. array of socket id => boolean value ON/OFF
     * @param array|bool[] $sockets
     */
    public function setSockets($sockets) {
        if (!$this->doLogin())
            return;

        foreach ($sockets as $socket => $state) {
            if (!in_array($socket, [1, 2, 3, 4]))
                continue;
            if (!in_array($state, [true, false]))
                continue;
            $params = ['cte' . $socket => (int)$state];
            $this->postRequest('http://' . $this->ip . '/', $params);
        }
        $this->doLogout();
    }

    /**
     * Sets status of a specific socket
     * @param int $socket
     * @param bool $value
     */
    public function setSocket($socket, $value) {
        $this->setSockets([$socket => $value]);
    }

    /**
     * Restarts selected sockets, input is an array of int socket ids
     * This requires NTP synchronized time on server and controlled device
     * @param array|int[] $sockets
     */
    public function restartSockets($sockets) {
        if (!$this->doLogin())
            return;

        $data = $this->createRestartData();
        foreach ($sockets as $socket) {
            if (!in_array($socket, [1, 2, 3, 4]))
                continue;
            $params = ['sch' . $socket => $data];
            $this->postRequest('http://' . $this->ip . '/', $params);
        }
        $this->doLogout();
    }

    /**
     * Restarts a specific socket (delay->off->delay->on)
     * This requires NTP synchronized time on server and controlled device
     * @param int $socket
     */
    public function restartSocket($socket) {
        $this->restartSockets([$socket]);
    }

    /**
     * @param string $url
     * @param array $fields
     * @return string|false
     */
    private function postRequest($url, $fields = []) {
        $fields_string_array = array();
        foreach ($fields as $key => $value) {
            $fields_string_array[] = $key . '=' . $value;
        }
        $fields_string = join('&', $fields_string_array);
        //open connection
        $ch = curl_init();

        // configure
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::REQUEST_TIMEOUT);
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        if ($this->debug) {
            echo "Calling " . $url . '?' . $fields_string . "...<br>\n";
        }
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);

        // provide html
        return $result;
    }

    /**
     * @param string $url
     * @param array $fields
     * @return string|false
     */
    private function getRequest($url, $fields = []) {
        $fields_string_array = array();
        foreach ($fields as $key => $value) {
            $fields_string_array[] = $key . '=' . $value;
        }
        $fields_string = join('&', $fields_string_array);
        //open connection
        $ch = curl_init();

        // configure
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::REQUEST_TIMEOUT);
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url . ($fields_string != '' ? '?' . $fields_string : ''));

        //execute post
        if ($this->debug) {
            echo "Calling " . $url . '?' . $fields_string . "...<br>\n";
        }
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);

        // provide html
        return $result;
    }

    /**
     * @return string
     */
    private function createRestartData() {
        $events = new EnerGenieSwitcherEvents();
        $events->addEvent(new EnerGenieSwitcherEvent(time() + self::RESTART_DELAY_OFF, false));
        $events->addEvent(new EnerGenieSwitcherEvent(time() + self::RESTART_DELAY_OFF + self::RESTART_DELAY_ON, true));
        return $events->encodeData();
    }
}