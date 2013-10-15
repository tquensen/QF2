<?php
//event listeners are loaded from the container by 'service', then method 'method' is called with the event object as parameter
//add event listeners to an event 'event.name' by appending an array to $config['events']['event.name']
//array contains of service name (service), method to call(method) and optional a priority (higher = earlier, default=0)
//the event listener object/service is loaded from the DI container (see dependencies.php for example)

//example:
/*
$config['events']['example'][] = array(
    'service' => 'listener.foo',
    'method' => 'doSomethingOnExampleEvent',
    'priority' => 100
);
*/
//qf core events
/*
 * init security - after the persistent/session user is initialized
 * event subject is the QF\Security instance
 * no event attributes
 * suitable for alternative authorization methods (IP-based, oAuth, token-based ...)
 * should call $security->login or similar
 * 
   $config['events']['security.init'][] = array(
    'service' => 'myListenerService',
    'method' => 'doSomethingOnSecurityInit',
    'priority' => 0
   );
 */