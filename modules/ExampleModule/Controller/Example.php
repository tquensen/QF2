<?php
namespace ExampleModule\Controller;

use \QF\Controller;

class Example extends Controller
{
    public function home($parameter)
    {
        $t = $this->qf->i18n->get('ExampleModule');
        return $this->qf->parse('ExampleModule', 'home', array('t' => $t));
    }
    
    public function staticPage($parameter)
    {
        if (empty($parameter['page']) || !preg_match('/[\w\-\+]/i', $parameter['page'])) {
            return $this->qf->callError();
        }
        
        //set title/description
        $t = $this->qf->i18n->get('ExampleModule');
        $titleKey = 'page_'.$parameter['page'].'_title';
        $descriptionKey = 'page_'.$parameter['page'].'_description';
        $title = $t->get($titleKey);
        $description = $t->get($descriptionKey);
        if ($title && $title != $titleKey) {
            $this->qf->config->page_title = $title;
        }
        if ($description && $description != $descriptionKey) {
            $this->qf->config->meta_description = $description;
        }

        return $this->qf->parse('ExampleModule', 'pages/'.$parameter['page'], array('t' => $t));
    }
}
