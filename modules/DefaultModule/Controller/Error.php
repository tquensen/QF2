<?php
namespace DefaultModule\Controller;

use \QF\Controller;

class Error extends Controller
{
    protected static $services = array('qf' => 'core', 'view', 'i18n', 'meta');
     
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
        return $view->parse('DefaultModule', 'error/error403'.$debugStr, $parameter);
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
        return $view->parse('DefaultModule', 'error/error404'.$debugStr, $parameter);
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
        return $view->parse('DefaultModule', 'error/error500'.$debugStr, $parameter);
    }
}
