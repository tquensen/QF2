<?php
namespace QF\Form\Element;

class Hidden extends Element
{
	protected $type = 'hidden';

    public function toArray($public = true)
    {
        $element = parent::toArray($public);
        if ($public && $this->alwaysDisplayDefault) {
            $element['value'] = $element['options']['defaultValue'];
        }
        return $element;
    }
}