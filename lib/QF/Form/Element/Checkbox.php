<?php
namespace QF\Form\Element;

class Checkbox extends Element
{
	protected $type = 'checkbox';

    public function setValue($value)
	{
        parent::setValue((bool) $value);
    }

}