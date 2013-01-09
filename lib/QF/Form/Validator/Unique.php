<?php
namespace QF\Form\Validator;

class Unique extends Validator
{

    public function validate($value)
    {
        if ($this->getOption('values'))
		{
			return (!in_array($value, $this->getOption('values')));
		}

        $entity = $this->getForm()->getEntity();
        
        if ($entity && $this->getOption('property')) {
            $property = $this->getOption('property');
        } elseif ($element = $this->getElement()) {
            $property = $element->getOption('entityProperty') ? $element->getOption('entityProperty') : $element->getName();
        }
        
        if (!empty($entity) && !empty($property) && method_exists((object) $entity, 'getRepository'))
        {
            try
            {
                $found = $entity->getRepository($entity->getDB())->loadOne($property, $value);
                if (!$found || $found->{$found->getIdentifier()} == $entity->{$entity->getIdentifier()}) {
                    return true;
                }
            }
            catch (Exception $e)
            {
                return false;
            }
        }
		
		return false;
    }

}

