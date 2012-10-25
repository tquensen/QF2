<?php
namespace ExampleModule\Controller;

use \QF\Controller,
    \QF\Exception\HttpException;


class Base extends Controller
{
    public function home($parameter, $qf)
    {
        $t = $qf->getI18n()->get('ExampleModule');
        return $qf->parse('ExampleModule', 'home', array('t' => $t));
    }
    
    public function staticPage($parameter, $qf)
    {
        if (empty($parameter['page']) || !preg_match('/[\w\-\+]/i', $parameter['page'])) {
            throw new HttpException('invalid page '.$parameter['page'], 404);
        }
        
        //set title/description
        $t = $qf->getI18n()->get('ExampleModule');
        $titleKey = 'page_'.$parameter['page'].'_title';
        $descriptionKey = 'page_'.$parameter['page'].'_description';
        $title = $t->get($titleKey);
        $description = $t->get($descriptionKey);
        if ($title && $title != $titleKey) {
            $qf->page_title = $title;
        }
        if ($description && $description != $descriptionKey) {
            $qf->meta_description = $description;
        }

        return $qf->parse('ExampleModule', 'pages/'.$parameter['page'], array('t' => $t));
    }
    
    
}
