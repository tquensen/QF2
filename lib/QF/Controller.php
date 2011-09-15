<?php
namespace QF;

class Controller
{
    /**
     *
     * @var \QF\Core
     */
    protected $qf = null;
    
    public function __construct(Core $qf)
    {
        $this->qf = $qf;
    }
}