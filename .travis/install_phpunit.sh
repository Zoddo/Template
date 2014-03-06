#!/bin/bash

pear config-set auto_discover 1
pear install pear.phpunit.de/PHPUnit

pear install phpunit/DbUnit
pear install phpunit/PHPUnit_TicketListener_GitHub
pear install phpunit/PHP_Invoker