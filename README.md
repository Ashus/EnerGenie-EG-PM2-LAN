EnerGenie-EG-PM2-LAN
====================

A PHP class that allows switching this IP multi-outlet power strip (the device costs around 80 € on Amazon Germany).

A usage example is provided with example.php, it allows to
  * Login to the device
  * Switch ports
  * Get the status of ports
  * Set a schedule to reboot specific socket



Socket rebooting notes
  - This feature uses the device scheduler. That means you can use it safely to reboot a router connecting you to it.
  - For this to work correctly, you need time synchronized via NTP on both server and Energenie device.
  - There is a 5s delay before it is turned off to mitigate any small time imprecision problems.
  - After that there is another 5s delay to make sure any connected devices are happy with restart.
  - Any other scheduled tasks for used socket are overwritten after using this.
