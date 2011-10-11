<?php
namespace QF;

use \QF\Exception\HttpException;

class Core
{
    protected $storage = array();

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function __get($key)
    {
        return isset($this->storage[$key]) ? $this->storage[$key] : null;
    }

    public function __set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    /**
     *
     * @param string $key the key of the config value to get or null to return the full config array
     * @param mixed $default the default value to return if $key is not found
     * @return mixed the config array or a specifig config value (if $key is set)
     */
    public function getConfig($key = null, $default = null)
    {
        return $this->config->get($key, $default);
    }

    /**
     *
     * @param string $key the key of the config value to set or null to replace the complete config
     * @param mixed $value the new value to set
     */
    public function setConfig($key = null, $value = null)
    {
        $this->config->set($key, $value);
    }


    /**
     * calls the action defined by $controller and $action and returns the output
     *
     * @param string $controller the controller
     * @param string $action the action
     * @param array $parameter parameters for the page
     * @return string the parsed output of the page
     */
    public function callAction($controller, $action, $parameter = array())
    {
        if (!class_exists($controller) || !method_exists($controller, $action)) {
            throw new HttpException('action not found', 404);
        }
        
        $controller = new $controller($this);
        return $controller->$action($parameter);
    }
    
    /**
     * calls the error page defined by $errorCode and shows $message
     *
     * @param string $errorCode the error page name (default error pages are 401, 403, 404, 500)
     * @param string $message a message to show on the error page, leave empty for default message depending on error code
     * @param Exception $exception an exception to display (only if QF_DEBUG = true)
     * @return string the parsed output of the error page
     */
    public function callError($errorCode = 404, $message = '', $exception = null)
    {
        return $this->callRoute('error'.$errorCode, array('message' => $message, 'exception' => $exception));
    }
    
    /**
     *
     * @param string $route the key of the route to get
     * @param array $parameter parameters for the page
     * @param bool $setAsMainRoute whether this route is the main call (set format, current_route and current_route_parameter config values) or not
     * @return @return string the parsed output of the page
     */
    public function callRoute($route, $parameter = array(), $setAsMainRoute = false)
    {
        $routeData = $this->routing->getRoute($route);
        if (!$routeData || empty($routeData['controller']) || empty($routeData['action'])) {
            throw new HttpException('page not found', 404);
        }
        
        if (!empty($routeData['rights']) && !$this->user->userHasRight($routeData['rights'])) {
            if ($this->user->getRole() === 'GUEST') {
                throw new Exception\HttpException('login required', 401);
            } else {
                throw new Exception\HttpException('permission denied', 403);
            }
        }


        if (!empty($routeData['parameter'])) {
            $parameter = array_merge($routeData['parameter'], $parameter);
        }
        
        if ($setAsMainRoute) {
            $this->setConfig('current_route', $route);
            $this->setConfig('current_route_parameter', $parameter);
            if (!empty($parameter['_format'])) {
                $this->setConfig('format', $parameter['_format']);
            }
        }
        return $this->callAction($routeData['controller'], $routeData['action'], $parameter);       
    }

    /**
     * parses the given page and returns the output
     *
     * inside the page, you have direct access to any given parameter
     *
     * @param string $module the module containing the page
     * @param string $view the name of the view file
     * @param array $parameter parameters for the page
     * @return string the parsed output of the page
     */
    public function parse($module, $view, $parameter = array())
    {
        $_theme = $this->getConfig('theme', null);
        $_themeString = $_theme ? 'themes/'.$_theme . '/' : '';
        $_format = $this->getConfig('format');
        $_formatString = $_format ? '.' . $_format : '';

        if ($_theme && file_exists(\QF_BASEPATH . 'templates/' .$_themeString. 'modules/' . $module . '/views/' . $page . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . 'templates/' .$_themeString. 'modules/' . $module . '/views/' . $page . $_formatString . '.php';
        } elseif ($_theme && !$_format && file_exists(\QF_BASEPATH . 'templates/' .$_themeString. 'modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php')) {
            $_file = \QF_BASEPATH . 'templates/' .$_themeString. 'modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php';
        } elseif (file_exists(\QF_BASEPATH . 'templates/modules/' . $module . '/views/' . $page . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . 'templates/modules/' . $module . '/views/' . $page . $_formatString . '.php';
        } elseif (!$_format && file_exists(\QF_BASEPATH . 'templates/modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php')) {
            $_file = \QF_BASEPATH . 'templates/modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php';
        } elseif (file_exists(\QF_BASEPATH . 'modules/' . $module . '/views/' . $page . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . 'modules/' . $module . '/views/' . $page . $_formatString . '.php';
        } elseif (!$_format && file_exists(\QF_BASEPATH . 'modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php')) {
            $_file = \QF_BASEPATH . 'modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php';
        } else {
            throw new Exception\HttpException('view not found', 404);
        }

        $qf = $this;
        extract($parameter, \EXTR_OVERWRITE);
        ob_start();
        require($_file);
        return ob_get_clean();
    }

    /**
     * parses the template with the given content
     *
     * inside the template, you have direct access to the page content $content
     *
     * @param string $content the parsed output of the current page
     * @return string the output of the template
     */
    public function parseTemplate($content)
    {
        $templateName = $this->getConfig('template');
        $_theme = $this->getConfig('theme', null);
        $_themeString = $_theme ? 'themes/'.$_theme . '/' : '';
        $_format = $this->getConfig('format');
        $_defaultFormat = $this->getConfig('default_format');
        $_file = false;

        if (is_array($templateName)) {
            if ($_format) {
                $templateName = isset($templateName[$_format]) ? $templateName[$_format] : (isset($templateName['all']) ? $templateName['all'] : null);
            } else {
                if (isset($templateName['default'])) {
                    $templateName = $templateName['default'];
                } else {
                    $templateName = isset($templateName[$_defaultFormat]) ? $templateName[$_defaultFormat] : (isset($templateName['all']) ? $templateName['all'] : null);
                }
            }
        }

        if ($templateName === false) {
            return $content;
        }

        if ($_format) {
            if ($_theme && $templateName && file_exists(\QF_BASEPATH . 'templates/' . $_themeString . '/' . $templateName . '.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . 'templates/' . $_themeString . '/' .$templateName . '.' . $_format . '.php';
            } elseif ($templateName && file_exists(\QF_BASEPATH . 'templates/' . $templateName . '.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . 'templates/' . $templateName . '.' . $_format . '.php';
            } elseif ($_theme && file_exists(\QF_BASEPATH . 'templates/' . $_themeString . 'default.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . 'templates/'. $_themeString . 'default.' . $_format . '.php';
            } elseif (file_exists(\QF_BASEPATH . 'templates/default.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . 'templates/default.' . $_format . '.php';
            }
        } elseif ($templateName) {
            if ($_theme && file_exists(\QF_BASEPATH . 'templates/' . $_themeString . $templateName . '.php')) {
                $_file = \QF_BASEPATH . 'templates/' . $_themeString . $templateName . '.php';
            } elseif (file_exists(\QF_BASEPATH . 'templates/' . $templateName . '.php')) {
                $_file = \QF_BASEPATH . 'templates/' . $templateName . '.php';
            } elseif ($_theme && file_exists(\QF_BASEPATH . 'templates/' . $_themeString . $templateName . '.' . $_defaultFormat . '.php')) {
                $_file = \QF_BASEPATH . 'templates/' . $_themeString . $templateName . '.' . $_defaultFormat . '.php';
            } elseif (file_exists(\QF_BASEPATH . 'templates/' . $templateName . '.' . $_defaultFormat . '.php')) {
                $_file = \QF_BASEPATH . 'templates/' . $templateName . '.' . $_defaultFormat . '.php';
            }
        }

        if (!$_file) {
            throw new Exception\HttpException('template not found', 404);
        }

        $qf = $this;
        ob_start();
        require($_file);
        return ob_get_clean();
    }

}