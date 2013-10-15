<?php
namespace DevModule\Task;

class Assets
{

    /**
     *
     * @var \QF\ViewManager 
     */
    protected $view;
    
    public function getView()
    {
        return $this->view;
    }

    public function setView(\QF\ViewManager $view)
    {
        $this->view = $view;
    }

    public function link($parameter)
    {
        $modules = $this->view->getModules();
        $templatePath = $this->view->getTemplatePath();
        $webPath = $this->view->getWebPath();
        
        if (!file_exists($webPath.'/modules')) {
            echo 'Created directory '.$webPath.'/modules'."\n";
            mkdir($webPath.'/modules');
        }
        if (!file_exists($webPath.'/templates')) {
            echo 'Created directory '.$webPath.'/templates'."\n";
            mkdir($webPath.'/templates');
        }

        foreach ($modules as $module => $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            if (file_exists($path.'/public') && !file_exists($webPath.'/modules/'.$module)) {
                symlink($path.'/public', $webPath.'/modules/'.$module);
                echo 'Created symlink '.$webPath.'/modules/'.$module . ' pointing to '.$path.'/public'."\n";
            }
            
        }
        
        foreach (scandir($templatePath) as $theme) {
            if (!is_dir($templatePath.'/'.$theme) || $theme == '.' || $theme == '..') {
                continue;
            }
            
            foreach (scandir($templatePath.'/'.$theme) as $folder) {
                if (!is_dir($templatePath.'/'.$theme.'/'.$folder) || $folder == '.' || $folder == '..') {
                    continue;
                }
                
                if ($folder == 'public') {
                    if (!file_exists($webPath.'/templates/'.$theme)) {
                        symlink($templatePath.'/'.$theme.'/'.$folder, $webPath.'/templates/'.$theme);
                        echo 'Created symlink '.$webPath.'/templates/'.$theme . ' pointing to '.$templatePath.'/'.$theme.'/'.$folder."\n";
                    }
                } else {
                    foreach (scandir($templatePath.'/'.$theme.'/'.$folder) as $subfolder) {
                        if (!is_dir($templatePath.'/'.$theme.'/'.$folder.'/'.$subfolder) || $subfolder == '.' || $subfolder == '..') {
                            continue;
                        }

                        if ($subfolder == 'public') {
                            if (!file_exists($webPath.'/templates/'.$theme.'/'.$folder)) {
                                if (!file_exists($webPath.'/templates/'.$theme)) {
                                    mkdir($webPath.'/templates/'.$theme);
                                }
                                symlink($templatePath.'/'.$theme.'/'.$folder.'/'.$subfolder, $webPath.'/templates/'.$theme.'/'.$folder);
                                echo 'Created symlink '.$webPath.'/templates/'.$theme.'/'.$folder . ' pointing to '.$templatePath.'/'.$theme.'/'.$folder.'/'.$subfolder."\n";
                            }
                        }
                    }
                }
            }
        }
        
        echo 'Done! All links created'."\n";
        
        
    }
    
    
}
