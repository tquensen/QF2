<?php
namespace ExampleModule\Controller;

use \QF\Exception\HttpException;


class Base
{
    /**
     *
     * @var \QF\Core 
     */
    protected $qf;
    
    /**
     *
     * @var \QF\ViewManager
     */
    protected $view;
    
    /**
     *
     * @var \QF\I18n
     */
    protected $i18n;
    
    /**
     *
     * @var \QF\Utils\Meta
     */
    protected $meta;
    
    public function getQf()
    {
        return $this->qf;
    }

    public function getView()
    {
        return $this->view;
    }

    public function getI18n()
    {
        return $this->i18n;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function setQf(\QF\Core $qf)
    {
        $this->qf = $qf;
    }

    public function setView(\QF\ViewManager $view)
    {
        $this->view = $view;
    }

    public function setI18n(\QF\I18n $i18n)
    {
        $this->i18n = $i18n;
    }

    public function setMeta(\QF\Utils\Meta $meta)
    {
        $this->meta = $meta;
    }

        
    public function homeAction($parameter)
    {
        $t = $this->i18n->get('ExampleModule');
        return $this->view->parse('ExampleModule', 'home', array('t' => $t));
    }
    
    public function staticPageAction($parameter)
    {
        if (empty($parameter['page']) || !preg_match('/[\w\-\+]/i', $parameter['page'])) {
            throw new HttpException('invalid page '.$parameter['page'], 404);
        }
        
        //set title/description
        $t = $this->i18n->get('ExampleModule');
        $titleKey = 'page_'.$parameter['page'].'_title';
        $descriptionKey = 'page_'.$parameter['page'].'_description';
        $title = $t->get($titleKey);
        $description = $t->get($descriptionKey);
        if ($title && $title != $titleKey) {
            $this->meta->setTitle($title);
        }
        if ($description && $description != $descriptionKey) {
            $this->meta->setDescription($description);
        }

        return $this->view->parse('ExampleModule', 'pages/'.$parameter['page'], array('t' => $t));
    }
    
    public function exampleWidget($parameter)
    {
        $slotName = $parameter['slot'];
        $slotData = $parameter['slotData'];
        
        return $this->view->parse('ExampleModule', 'widget', array());
    }
    
}
