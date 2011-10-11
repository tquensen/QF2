<?php
namespace QF;

class I18n
{
    /**
     * @var \QF\Core
     */
    protected $qf = null;
    
    /**
     * @var \QF\Translation
     */
    protected $translations = null;
    
    protected $currentLanguage = null;
    
    protected $data = array();

    /**
     * initializes the translation class
     *
     * @param \QF\Core $qf
     * @param string $translationDir the path to the translation files
     * @param string $language the target language (leave blank to use the default language)
     */
    function __construct(Core $qf, $translationDir, $language = null)
    {
        $this->qf = $qf;

        $defaultLanguage = $qf->getConfig('default_language', 'en');
        if (!$language || !in_array($language, $qf->getConfig('languages', array()))) {
            $language = $defaultLanguage;
        }

        $qf->setConfig('current_language', $language);
        $this->currentLanguage = $language;
        
        $i18n = &$this->data;
        if (file_exists($translationDir . '/' .$language . '.php')) {
            include($translationDir . '/' .$language . '.php');
        }
        $this->translations['default'] = new Translation(!empty($this->data['default']) ? $this->data['default'] : array());
    }
    
    public function loadModule($module)
    {
        if (file_exists(\QF_BASEPATH . 'modules/'.$module.'/data/i18n/'.$this->currentLanguage . '.php')) {
            if (!isset($this->data[$module])) {
                $this->data[$module] = array();
            }
            $i18n = &$this->data[$module];
            include \QF_BASEPATH . 'modules/'.$module.'/data/i18n/'.$this->currentLanguage . '.php';
        }
    }

    /**
     *
     * @return \QF\Translation
     */
    public function get($ns = 'default')
    {
        if (empty($this->translations[$ns])) {
            $this->translations[$ns] = new Translation(!empty($i18n[$ns]) ? $i18n[$ns] : array());
        }
        return $this->translations[$ns];
    }
}