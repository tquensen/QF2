<?php
namespace ExampleModule\Task;

use \QF\Controller;

class Example extends Controller
{
    public function exampleTask($parameter, $c)
    {
        var_dump($parameter);
    }
}
