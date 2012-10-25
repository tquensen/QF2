<?php
namespace DevModule\Task;

use \QF\Controller;

class Install extends Controller
{
    public function install($parameter, $qf)
    {
        if (!$parameter['entity']) {
            return 'Error: no Entity given';
        }
        
        if (!class_exists($parameter['entity'])) {
            return 'Error: Class '.$parameter['entity'].' does not exist';
        }
        
       $type = $parameter['storageKey'];
       
       $c = $qf->getContainer();
       $db = $c[$type]->get();
        
        
        $className = $parameter['entity'];
        
        if ($parameter['mode'] == 'install') {
            try {
                $status = $className::install($db, $parameter['from'], $parameter['to']);
                if ($status !== true && $status !== null) {
                    return 'An error occurred: ' . $status;
                } else {
                    return 'Entity ' . $parameter['entity'] . ' was installed successfully' . ($parameter['from'] ? ' from ' . $parameter['from'] : '') . ($parameter['to'] ? ' to ' . $parameter['to'] : '') . '!';
                }
            } catch (Exception $e) {
                return 'An error occurred: ' . $e->getMessage();
            }
        } elseif ($parameter['mode'] == 'uninstall') {
            try {
                $status = $className::uninstall($db, $parameter['from'], $parameter['to']);
                if ($status !== true && $status !== null) {
                    return 'An error occurred: ' . $status;
                } else {
                    return 'Entity ' . $parameter['entity'] . ' was uninstalled successfully' . ($parameter['from'] ? ' from ' . $parameter['from'] : '') . ($parameter['to'] ? ' to ' . $parameter['to'] : '') . '!';
                }
            } catch (Exception $e) {
                return 'An error occurred: ' . $e->getMessage();
            }
        }
        
        
    }
    
    
}
