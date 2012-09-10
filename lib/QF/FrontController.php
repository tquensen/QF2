<?php
namespace QF;

use \QF\Exception\HttpException;

class FrontController
{
    
    protected $parameter = array();
    
    protected $theme = null;
    protected $format = null;
    protected $defaultFormat = null;
    protected $template = null;
    
    public function __construct($parameter)
    {
        $this->parameter = $parameter;
    }
    
    public function __get($key)
    {
        return !empty($this->parameter[$key]) ? $this->parameter[$key] : null;
    }
    
    public function __set($key, $value)
    {
        $this->parameter[$key] = $value;
    }
    
    /**
     * calls the action defined by $controller and $action and returns the output
     *
     * @param string $controller the controller
     * @param string $action the action
     * @param array $parameter parameters for the page
     * @param mixed $c the DI container
     * @return string the parsed output of the page
     */
    public function callAction($controller, $action, $parameter = array(), $c = null)
    {
        if (!class_exists($controller) || !method_exists($controller, $action)) {
            throw new HttpException('action not found', 404);
        }
        
        $controller = new $controller();
        return $controller->$action($parameter, $c);
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
        $_theme = $this->theme;
        $_themeString = $_theme ? 'themes/'.$_theme . '/' : '';
        $_format = isset($parameter['_format']) ? $parameter['_format'] : $this->format;
        $_formatString = $_format ? '.' . $_format : '';

        if ($_theme && file_exists(\QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $view . $_formatString . '.php';
        } elseif ($_theme && !$_format && file_exists(\QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php';
        } elseif (file_exists(\QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $view . $_formatString . '.php';
        } elseif (!$_format && file_exists(\QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php';
        } elseif (file_exists(\QF_BASEPATH . '/modules/' . $module . '/views/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/modules/' . $module . '/views/' . $view . $_formatString . '.php';
        } elseif (!$_format && file_exists(\QF_BASEPATH . '/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php';
        } else {
            throw new HttpException('view not found', 404);
        }

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
     * @param array $parameter parameters for the page
     * @return string the output of the template
     */
    public function parseTemplate($content, $parameter = array())
    {
        $_templateName = $this->template;
        $_theme = $this->theme;
        $_themeString = $_theme ? 'themes/'.$_theme . '/' : '';
        $_format = $this->format;
        $_defaultFormat = $this->defaultFormat;
        $_file = false;

        if (is_array($_templateName)) {
            if ($_format) {
                $_templateName = isset($_templateName[$_format]) ? $_templateName[$_format] : (isset($_templateName['all']) ? $_templateName['all'] : null);
            } else {
                if (isset($_templateName['default'])) {
                    $_templateName = $_templateName['default'];
                } else {
                    $_templateName = isset($_templateName[$_defaultFormat]) ? $_templateName[$_defaultFormat] : (isset($_templateName['all']) ? $_templateName['all'] : null);
                }
            }
        }

        if ($_templateName === false) {
            return $content;
        }

        if ($_format) {
            if ($_theme && $_templateName && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . '/' . $_templateName . '.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . '/' .$_templateName . '.' . $_format . '.php';
            } elseif ($_templateName && file_exists(\QF_BASEPATH . '/templates/' . $_templateName . '.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_templateName . '.' . $_format . '.php';
            } elseif ($_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . 'default.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/'. $_themeString . 'default.' . $_format . '.php';
            } elseif (file_exists(\QF_BASEPATH . '/templates/default.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/default.' . $_format . '.php';
            }
        } elseif ($_templateName) {
            if ($_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.php';
            } elseif (file_exists(\QF_BASEPATH . '/templates/' . $_templateName . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_templateName . '.php';
            } elseif ($_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.' . $_defaultFormat . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.' . $_defaultFormat . '.php';
            } elseif (file_exists(\QF_BASEPATH . '/templates/' . $_templateName . '.' . $_defaultFormat . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_templateName . '.' . $_defaultFormat . '.php';
            }
        }

        if (!$_file) {
            throw new HttpException('template not found', 404);
        }

        extract($parameter, \EXTR_OVERWRITE);
        ob_start();
        require($_file);
        return ob_get_clean();
    }
    
    public function getTheme()
    {
        return $this->theme;
    }
    
    public function getFormat()
    {
        return $this->format;
    }
    
    public function getDefaultFormat()
    {
        return $this->defaultFormat;
    }
    
    public function getTemplate()
    {
        return $this->template;
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }
    
    public function setDefaultFormat($defaultFormat)
    {
        $this->defaultFormat = $defaultFormat;
    }
    
    public function setTemplate($template)
    {
        $this->template = $template;
    }

}