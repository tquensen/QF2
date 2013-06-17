<?php

//event listeners are loaded from the container by $key, them method $method is called with the event object as parameter
//add event listeners to an event 'event.name' by appending an array to $config['events']['event.name']
//array contains of service name ($key), method to call($method) and optional a priority (higher = earlier, default=0)
//the event listener object/service is loaded from the DI container (see dependencies.php for example)

//example:
//$config['events']['example'][] = array('listener.foo', 'doSomethingOnExampleEvent', 100);

//qf core events
$config['events']['security.checkRouteRights'][] = array('security', 'checkRouteRightsListener', 0);