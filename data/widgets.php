<?php

//assign widgets to slots and define when it should be shown
$slots['sidebar']['example.widget'] = $widgets['example.widget'];
$slots['sidebar']['example.widget']['rights'] = 'user'; //define rights
$slots['sidebar']['example.widget']['theme'] = 'all'; //defaults to "all" / string or array / only show the widget on these themes
$slots['sidebar']['example.widget']['template'] = 'all'; //defaults to "all" / string or array / only show the widget on these templates
$slots['sidebar']['example.widget']['format'] = 'all'; //defaults to "all" / string or array / only show the widget on these formats
$slots['sidebar']['example.widget']['show'] = 'home'; //only show on these routes (string or array) (not compatible with 'hide')
$slots['sidebar']['example.widget']['hide'] = 'user.login'; //hide widget on these routes (string or array) (not compatible with 'show')
$slots['sidebar']['example.widget']['priority'] = 0; //ordering of the widgets in the same slot (higher = earlier, default=0)