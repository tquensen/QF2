<?php
namespace ExampleModule\Task;

class Example
{
    /**
     *
     * @var \QF\Cli
     */
    protected $cli;
    
    public function getCli()
    {
        return $this->cli;
    }

    public function setCli(\QF\Cli $cli)
    {
        $this->cli = $cli;
    }

    public function exampleTask($parameter)
    {
        var_dump($parameter);
    }
}
