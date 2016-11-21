Nest-Boost
==========

Introduction
-------------
Adds a facility to 'Boost' the temperature of the Nest Thermostat for a defined period.
This is for those days when your home is already warm but you really need the heating on for an hour to dry washing.

This program manages the 'Boost' by ensuring your Nest target temperature remains above the actual temperature for the defined period, before reverting the target temperature once the time elapses.

Nest-Boost relies on the unofficial Nest API (https://github.com/gboudreau/nest-api/)

![Screenshot](https://cloud.githubusercontent.com/assets/12716504/20485603/483f38d6-aff4-11e6-8286-9e77a6980b51.png)

Installation
-------------
To install you'll need a webserver running PHP and MySQL. Additionally, you'll need to know how to add cron jobs.

Your database should be configured as below
```
+-----------------+------------+------+-----+---------+-------+
| Field           | Type       | Null | Key | Default | Extra |
+-----------------+------------+------+-----+---------+-------+
| startTargetTemp | float      | YES  |     | NULL    |       |
| startActualTemp | float      | YES  |     | NULL    |       |
| startTime       | datetime   | YES  |     | NULL    |       |
| totalMins       | int(11)    | YES  |     | NULL    |       |
| complete        | varchar(1) | YES  |     | NULL    |       |
+-----------------+------------+------+-----+---------+-------+
```
You should add the following cron job
```
*/10 * * * * /usr/bin/wget -q -O nestboost_cron.log https://localhost/Nest-Boost/boost_control.php --no-check-certificate
```

Notes
-----
index.php is used to control Nest-Boost webpage. Here you can activate the Boost function and view the history of previous triggers.

boost_trigger.php is called from the form, and logs the request into the database before then calling boost_control.php

boost_control.php is called once from boost_trigger.php to populate the current target temperature and raise the target temperature of your Nest to 1 degree higher than the actual temperature.
It is also called via cron every 10 mins to ensure that the target temperature is still higher than the actual temperature and if not, add an additional 1 degree.
Finally once the Boost time has elapsed it will set the job in the database to completed, and lower the target temperature of your Nest to the pre-Boost target temperature.
