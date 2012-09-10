<?php
namespace QF;

class I18n
{
    
    /**
     * @var \QF\Translation[]
     */
    protected $translations = null;
    
    protected $currentLanguage = null;
    
    protected $data = array();

    /**
     * initializes the translation class
     *
     * @param string $translationDir the path to the translation files
     * @param string $language the target language
     */
    function __construct($translationDir, $language)
    {
        $this->currentLanguage = $language;
        
        $i18n = &$this->data;
        if (file_exists($translationDir . '/' .$language . '.php')) {
            include($translationDir . '/' .$language . '.php');
        }
        $this->translations['default'] = new Translation(!empty($this->data['default']) ? $this->data['default'] : array());
    }
    
    public function loadModule($module)
    {
        if (file_exists(\QF_BASEPATH . '/modules/'.$module.'/data/i18n/'.$this->currentLanguage . '.php')) {
            if (!isset($this->data[$module])) {
                $this->data[$module] = array();
            }
            $i18n = &$this->data[$module];
            include \QF_BASEPATH . '/modules/'.$module.'/data/i18n/'.$this->currentLanguage . '.php';
        }
    }

    /**
     *
     * @return \QF\Translation
     */
    public function get($ns = 'default')
    {
        if (empty($this->translations[$ns])) {
            $this->translations[$ns] = new Translation(!empty($this->data[$ns]) ? $this->data[$ns] : array());
        }
        return $this->translations[$ns];
    }
}