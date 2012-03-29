<?php
namespace QF\Form\Element;

class Button extends Element
{
	protected $type = 'button';

	public function setValue($value)
	{
	}

	public function updateEntity($entity)
	{
	}

    public function toArray($public = true)
    {
        $element = parent::toArray($public);
        if ($public) {
            $element['options']['type'] = $this->options['type'];
        }
        return $element;
    }
}