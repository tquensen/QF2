<?php
namespace ExampleModule\Task;

use \QF\Controller;

class Example extends Controller
{
    public function exampleTask($parameter, $cli)
    {
        $c = $cli->getContainer();
        var_dump($parameter);
    }
}
