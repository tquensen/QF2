<?php
namespace ExampleModule\Controller;

use \QF\Controller;


class Base extends Controller
{
    public function home($parameter, $c)
    {
        $t = $c['i18n']->get('ExampleModule');
        return $c['controller']->parse('ExampleModule', 'home', array('t' => $t));
    }
    
    public function staticPage($parameter, $c)
    {
        if (empty($parameter['page']) || !preg_match('/[\w\-\+]/i', $parameter['page'])) {
            return $c['controller']->callError();
        }
        
        //set title/description
        $t = $c['i18n']->get('ExampleModule');
        $titleKey = 'page_'.$parameter['page'].'_title';
        $descriptionKey = 'page_'.$parameter['page'].'_description';
        $title = $t->get($titleKey);
        $description = $t->get($descriptionKey);
        if ($title && $title != $titleKey) {
            $c['controller']->page_title = $title;
        }
        if ($description && $description != $descriptionKey) {
            $c['controller']->meta_description = $description;
        }

        return $c['controller']->parse('ExampleModule', 'pages/'.$parameter['page'], array('t' => $t));
    }
    
    
}
