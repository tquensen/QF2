<?php
namespace QF\Form\Validator;

use \QF\Form\Element\Element;

class Equals extends Validator
{
	public function validate($value)
	{
        $checkValue = $this->getOption('value');
        if (is_object($checkValue) && $checkValue instanceof Element)
        {
            $checkValue = $checkValue->value;
        }

		return (bool) ($this->getOption('strict')) ? $value === $checkValue : $value == $checkValue;
	}
}