<?php
//define your widgets here, add them to a module-internal slot or let them be added in the "global/app" widget config
$widgets['example.widget'] = array(
    'service' => 'examplemodule.controller.base',
    'action' => 'exampleWidget',
    'parameter' => array('something' => 'value'),
);

//add widget to a slot
$slots['example.unusedSlot']['example.widget'] = $widgets['example.widget'];
