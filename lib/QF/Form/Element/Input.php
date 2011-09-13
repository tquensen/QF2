<?php
namespace QF\Form\Element;

class Input extends Element
{
	protected $type = 'input';
    
    public function __construct($name = false, $options = array(), $validators = array())
	{
		parent::__construct($name, $options, $validators);
		if (!isset($this->options['type']))
		{
			$this->options['type'] = 'text';
		}
	}
    
    public function toArray($public = true)
    {
        $element = parent::toArray($public);
        if ($public) {
            $element['inputType'] = $this->options['type'];
        }
        return $element;
    }
}