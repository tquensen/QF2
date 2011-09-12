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
     * @param bool $isMainRoute whether this page call is the main call (used as main content in the template) or not
     * @return string the parsed output of the page
     */
    public function callAction($controller, $action, $parameter = array(), $isMainRoute = false)
    {
        if (!class_exists($controller) || !method_exists($controller, $action)) {
            throw new HttpException('page not found', 404);
        }
        
        if ($isMainRoute) {
            $this->setConfig('current_module', $module);
            $this->setConfig('current_page', $page);
        }
        $controller = new $controller($this);
        return $controller->$action($parameter);
    }

    /**
     * parses the given page and returns the output
     *
     * inside the page, you have direct access to any given parameter
     *
     * @param string $module the module containing the page
     * @param string $page the page
     * @param array $parameter parameters for the page
     * @return string the parsed output of the page
     */
    public function parse($module, $view, $parameter = array())
    {
        $theme = $this->getConfig('theme', null);
        $themeString = $theme ? 'themes/'.$theme . '/' : '';
        $format = $this->getConfig('format');
        $formatString = $format ? '.' . $format : '';

        if ($theme && file_exists(\QF_BASEPATH . 'templates/' .$themeString. 'modules/' . $module . '/views/' . $page . $formatString . '.php')) {
            $file = \QF_BASEPATH . 'templates/' .$themeString. 'modules/' . $module . '/views/' . $page . $formatString . '.php';
        } elseif ($theme && !$format && file_exists(\QF_BASEPATH . 'templates/' .$themeString. 'modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php')) {
            $file = \QF_BASEPATH . 'templates/' .$themeString. 'modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php';
        } elseif (file_exists(\QF_BASEPATH . 'templates/modules/' . $module . '/views/' . $page . $formatString . '.php')) {
            $file = \QF_BASEPATH . 'templates/modules/' . $module . '/views/' . $page . $formatString . '.php';
        } elseif (!$format && file_exists(\QF_BASEPATH . 'templates/modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php')) {
            $file = \QF_BASEPATH . 'templates/modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php';
        } elseif (file_exists(\QF_BASEPATH . 'modules/' . $module . '/views/' . $page . $formatString . '.php')) {
            $file = \QF_BASEPATH . 'modules/' . $module . '/views/' . $page . $formatString . '.php';
        } elseif (!$format && file_exists(\QF_BASEPATH . 'modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php')) {
            $file = \QF_BASEPATH . 'modules/' . $module . '/views/' . $page . '.' . $this->getConfig('default_format') . '.php';
        } else {
            throw new HttpException('page not found', 404);
        }

        $qf = $this;
        extract($parameter, EXTR_OVERWRITE);
        ob_start();
        require($file);
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
        $theme = $this->getConfig('theme', null);
        $themeString = $theme ? 'themes/'.$theme . '/' : '';
        $format = $this->getConfig('format');
        $defaultFormat = $this->getConfig('default_format');
        $file = false;

        if (is_array($templateName)) {
            if ($format) {
                $templateName = isset($templateName[$format]) ? $templateName[$format] : (isset($templateName['all']) ? $templateName['all'] : null);
            } else {
                if (isset($templateName['default'])) {
                    $templateName = $templateName['default'];
                } else {
                    $templateName = isset($templateName[$defaultFormat]) ? $templateName[$defaultFormat] : (isset($templateName['all']) ? $templateName['all'] : null);
                }
            }
        }

        if ($templateName === false) {
            return $content;
        }

        if ($format) {
            if ($theme && $templateName && file_exists(\QF_BASEPATH . 'templates/' . $themeString . '/' . $templateName . '.' . $format . '.php')) {
                $file = \QF_BASEPATH . 'templates/' . $themeString . '/' .$templateName . '.' . $format . '.php';
            } elseif ($templateName && file_exists(\QF_BASEPATH . 'templates/' . $templateName . '.' . $format . '.php')) {
                $file = \QF_BASEPATH . 'templates/' . $templateName . '.' . $format . '.php';
            } elseif ($theme && file_exists(\QF_BASEPATH . 'templates/' . $themeString . 'default.' . $format . '.php')) {
                $file = \QF_BASEPATH . 'templates/'. $themeString . 'default.' . $format . '.php';
            } elseif (file_exists(\QF_BASEPATH . 'templates/default.' . $format . '.php')) {
                $file = \QF_BASEPATH . 'templates/default.' . $format . '.php';
            }
        } elseif ($templateName) {
            if ($theme && file_exists(\QF_BASEPATH . 'templates/' . $themeString . $templateName . '.php')) {
                $file = \QF_BASEPATH . 'templates/' . $themeString . $templateName . '.php';
            } elseif (file_exists(\QF_BASEPATH . 'templates/' . $templateName . '.php')) {
                $file = \QF_BASEPATH . 'templates/' . $templateName . '.php';
            } elseif ($theme && file_exists(\QF_BASEPATH . 'templates/' . $themeString . $templateName . '.' . $defaultFormat . '.php')) {
                $file = \QF_BASEPATH . 'templates/' . $themeString . $templateName . '.' . $defaultFormat . '.php';
            } elseif (file_exists(\QF_BASEPATH . 'templates/' . $templateName . '.' . $defaultFormat . '.php')) {
                $file = \QF_BASEPATH . 'templates/' . $templateName . '.' . $defaultFormat . '.php';
            }
        }

        if (!$file) {
            throw new HttpException('page not found', 404);
        }

        $qf = $this;
        ob_start();
        require($file);
        return ob_get_clean();
    }

    /**
     * calls the error page defined by $errorCode and shows $message
     *
     * @param string $errorCode the error page name (default error pages are 401, 403, 404, 500)
     * @param string $message a message to show on the error page
     * @param Exception $exception an exception to display (only if errorCode = 500 and QF_DEBUG = true)
     * @return string the parsed output of the error page
     */
    public function callError($errorCode = 404, $message = '', $exception = null)
    {
        $route = $this->routing->getRoute('error'.$errorCode.'_route');
        if (!$route) {
            $route = $this->routing->getRoute('error500_route');
        }
        return $this->callAction($route['controller'], $route['action'], array('message' => $message, 'exception' => $exception));
    }

}