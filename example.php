<?php

require_once __DIR__ . '/class.EnerGenieSwitcher.php';

/**
 * Initialize with connection parameters (ip address and credentials)
 */
$egs = new EnerGenieSwitcher('1.2.3.4', 'my-password');

/**
 * Set socket states ON or OFF
 */
$egs->setSockets([1 => true, 2 => false]);
$egs->setSocket(3, false);

/**
 * Verify the status of sockets
 */
var_dump($egs->getSockets());
var_dump($egs->getSocket(1));

/**
 * Restart selected sockets
 */
$egs->restartSockets([1, 3]);
