<?php
namespace DefaultModule\Controller;

use \QF\Controller;

class Error extends Controller
{
    public function error401($parameter)
    {
        header('HTTP/1.1 401 Unauthorized', true, 401);
        
        $t = $this->qf->i18n->get('DefaultModule');
        
        $this->qf->config->page_title = $t->error401Title;
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $this->qf->parse('DefaultModule', 'error/error401'.$debugStr, $parameter);
    }
    
    public function error403($parameter)
    {
        header('HTTP/1.1 403 Forbidden', true, 403);
        
        $t = $this->qf->i18n->get('DefaultModule');
        
        $this->qf->config->page_title = $t->error403Title;
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $this->qf->parse('DefaultModule', 'error/error403'.$debugStr, $parameter);
    }
    
    public function error404($parameter)
    {
        header('HTTP/1.1 404 Not Found', true, 404);
        
        $t = $this->qf->i18n->get('DefaultModule');
        
        $this->qf->config->page_title = $t->error404Title;
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $this->qf->parse('DefaultModule', 'error/error404'.$debugStr, $parameter);
    }
    
    public function error500($parameter)
    {
        header('500 Internal Server Error', true, 500);
        
        $t = $this->qf->i18n->get('DefaultModule');
        
        $this->qf->config->page_title = $t->error500Title;
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $this->qf->parse('DefaultModule', 'error/error500'.$debugStr, $parameter);
    }
}
