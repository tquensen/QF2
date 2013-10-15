<?php
namespace DefaultModule\Controller;

class Error
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

        
    public function error401($parameter)
    {
        header('HTTP/1.1 401 Unauthorized', true, 401);
        
        $t = $this->i18n->get('DefaultModule');
        
        $this->meta->setTitle($t->error401Title);
        
        if (empty($parameter['message'])) {
            $parameter['message'] = 'login required';
        }
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $this->view->parse('DefaultModule', 'error/error401'.$debugStr, $parameter);
    }
    
    public function error403($parameter)
    {
        header('HTTP/1.1 403 Forbidden', true, 403);
        
        $t = $this->i18n->get('DefaultModule');
        
        $this->meta->setTitle($t->error403Title);
        
        if (empty($parameter['message'])) {
            $parameter['message'] = 'permission denied';
        }
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $this->view->parse('DefaultModule', 'error/error403'.$debugStr, $parameter);
    }
    
    public function error404($parameter)
    {
        header('HTTP/1.1 404 Not Found', true, 404);
        
        $t = $this->i18n->get('DefaultModule');
        
        $this->meta->setTitle($t->error404Title);
        
        if (empty($parameter['message'])) {
            $parameter['message'] = 'page not found';
        }
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $this->view->parse('DefaultModule', 'error/error404'.$debugStr, $parameter);
    }
    
    public function error500($parameter)
    {
        header('500 Internal Server Error', true, 500);
        
        $t = $this->i18n->get('DefaultModule');
        
        $this->meta->setTitle($t->error500Title);
        
        if (empty($parameter['message'])) {
            $parameter['message'] = 'server error';
        }
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $this->view->parse('DefaultModule', 'error/error500'.$debugStr, $parameter);
    }
}
