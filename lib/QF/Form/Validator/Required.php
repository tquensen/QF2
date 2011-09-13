<?php
namespace QF\Form\Validator;

use \QF\Form\Element\Element;

class Required extends Validator
{
	public function validate($value)
	{
		return (bool) $value;
	}

    public function setElement(Element $element)
    {
        parent::setElement($element);
        $this->element->required = true;
    }
}