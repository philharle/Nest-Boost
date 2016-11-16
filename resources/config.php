<?php
// Your Nest username and password.
define('USERNAME', 'user');
define('PASSWORD', 'pass');

// The timezone you're in.
// See http://php.net/manual/en/timezones.php for the possible values.
$us_timezones = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, 'US');
date_default_timezone_set('Europe/London');

//Database settings
$hostname='host';
$username='user';
$password='pass';
$dbname='database';