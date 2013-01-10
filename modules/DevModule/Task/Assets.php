<?php
namespace DevModule\Task;

use \QF\Controller;

class Assets extends Controller
{
    public function link($parameter, $c)
    {

        /* @var $qf \QF\Core */
        $qf = $c['core'];
        $modulePath = $qf->getModulePath();
        $templatePath = $qf->getTemplatePath();
        $webPath = $qf->getWebPath();
        
        if (!file_exists($webPath.'/modules')) {
            echo 'Created directory '.$webPath.'/modules'."\n";
            mkdir($webPath.'/modules');
        }
        if (!file_exists($webPath.'/templates')) {
            echo 'Created directory '.$webPath.'/templates'."\n";
            mkdir($webPath.'/templates');
        }

        foreach (scandir($modulePath) as $module) {
            if (!is_dir($modulePath.'/'.$module) || $module == '.' || $module == '..') {
                continue;
            }
            
            foreach (scandir($modulePath.'/'.$module) as $folder) {
                if (!is_dir($modulePath.'/'.$module.'/'.$folder) || $folder == '.' || $folder == '..') {
                    continue;
                }
                
                if ($folder == 'public') {
                    if (!file_exists($webPath.'/modules/'.$module)) {
                        symlink($modulePath.'/'.$module.'/'.$folder, $webPath.'/modules/'.$module);
                        echo 'Created symlink '.$webPath.'/modules/'.$module . ' pointing to '.$modulePath.'/'.$module.'/'.$folder."\n";
                    }
                } else {
                    foreach (scandir($modulePath.'/'.$module.'/'.$folder) as $subfolder) {
                        if (!is_dir($modulePath.'/'.$module.'/'.$folder.'/'.$subfolder) || $subfolder == '.' || $subfolder == '..') {
                            continue;
                        }

                        if ($subfolder == 'public') {
                            if (!file_exists($webPath.'/modules/'.$module.'/'.$folder)) {
                                if (!file_exists($webPath.'/modules/'.$module)) {
                                    mkdir($webPath.'/modules/'.$module);
                                }
                                symlink($modulePath.'/'.$module.'/'.$folder.'/'.$subfolder, $webPath.'/modules/'.$module.'/'.$folder);
                                echo 'Created symlink '.$webPath.'/modules/'.$module.'/'.$folder . ' pointing to '.$modulePath.'/'.$module.'/'.$folder.'/'.$subfolder."\n";
                            }
                        }
                    }
                }
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
