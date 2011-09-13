<?php
namespace QF\Form\Element;

class Password extends Element
{
	protected $type = 'password';

    public function toArray($public = true)
    {
        $element = parent::toArray($public);
        if ($public) {
            $element['value'] = '';
        }
        return $element;
    }
}