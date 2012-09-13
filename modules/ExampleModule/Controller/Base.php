<?php
namespace ExampleModule\Controller;

use \QF\Controller,
    \QF\Exception\HttpException;


class Base extends Controller
{
    public function home($parameter, $c)
    {
        $t = $c['i18n']->get('ExampleModule');
        return $c['core']->parse('ExampleModule', 'home', array('t' => $t));
    }
    
    public function staticPage($parameter, $c)
    {
        if (empty($parameter['page']) || !preg_match('/[\w\-\+]/i', $parameter['page'])) {
            throw new HttpException('invalid page '.$parameter['page'], 404);
        }
        
        //set title/description
        $t = $c['i18n']->get('ExampleModule');
        $titleKey = 'page_'.$parameter['page'].'_title';
        $descriptionKey = 'page_'.$parameter['page'].'_description';
        $title = $t->get($titleKey);
        $description = $t->get($descriptionKey);
        if ($title && $title != $titleKey) {
            $c['core']->page_title = $title;
        }
        if ($description && $description != $descriptionKey) {
            $c['core']->meta_description = $description;
        }

        return $c['core']->parse('ExampleModule', 'pages/'.$parameter['page'], array('t' => $t));
    }
    
    
}
