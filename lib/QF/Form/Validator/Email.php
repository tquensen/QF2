<?php
namespace QF\Form\Validator;

class Email extends Validator
{
	public function validate($value)
	{
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}
}