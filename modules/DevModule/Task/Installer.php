<?php

namespace DevModule\Task;

use \QF\Controller;

class Installer extends Controller
{

    public function install($parameter, $c)
    {
        if (!$parameter['entity']) {
            return 'Error: no Entity given';
        }

        if (!class_exists($parameter['entity'])) {
            return 'Error: Class ' . $parameter['entity'] . ' does not exist';
        }
        
        if (!($parameter['entity'] instanceof \QF\DB\Entity)) {
            return 'Error: Class ' . $parameter['entity'] . ' is not an \\QF\\DB\\Entity';
        }

        $type = $parameter['storageKey'];

        $db = $c[$type]->get();


        $className = $parameter['entity'];

        $from = $parameter['from'];
        $to = $parameter['to'];

        $res = $db->query('SHOW TABLES LIKE "_qf_version"');
        $row = $res->fetch_assoc();
        if (empty($row)) {
            $db->query('CREATE TABLE _qf_version (id VARCHAR(255), version INT(11), PRIMARY KEY (id)) ENGINE=INNODB DEFAULT CHARSET=utf8');
        }
        
        try {
            if (empty($from)) {
                $res = $qb->query('SELECT version FROM _qf_version WHERE id = "'.$className::getTableName().'"');
                $row = $res->fetch_assoc();
                if (!empty($row['version'])) {
                    $from = $row['version'];
                } else {
                    $from = 0;
                }
            }

            if (empty($to)) {
                $to = $className::getMaxDatabaseVersion();
            }
            $status = $className::install($db, $from, $to);
            if ($status !== true && $status !== null) {
                return 'An error occurred: ' . $status;
            } else {
                $res = $qb->query('SELECT version FROM _qf_version WHERE id = "'.$className::getTableName().'"');
                $row = $res->fetch_assoc();
                if (!empty($row['version'])) {
                    $qb->query('UPDATE _qf_version SET version = "'.$to.'" WHERE id = "'.$className::getTableName().'"');
                } else {
                    $qb->query('INSERT INTO _qf_version SET id = "'.$className::getTableName().'", version = "'.$to.'"');
                }
                return 'Entity ' . $parameter['entity'] . ' was installed successfully' . ($from ? ' from ' . $from : '') . ($to ? ' to ' . $to : '') . '!';
            }
        } catch (Exception $e) {
            return 'An error occurred: ' . $e->getMessage();
        }
    }
    
    public function installMongo($parameter, $c)
    {
        if (!$parameter['entity']) {
            return 'Error: no Entity given';
        }

        if (!class_exists($parameter['entity'])) {
            return 'Error: Class ' . $parameter['entity'] . ' does not exist';
        }
        
        if (!($parameter['entity'] instanceof \QF\Mongo\Entity)) {
            return 'Error: Class ' . $parameter['entity'] . ' is not an \\QF\\Mongo\\Entity';
        }

        $type = $parameter['storageKey'];

        $db = $c[$type]->get();

        $className = $parameter['entity'];

        $from = $parameter['from'];
        $to = $parameter['to'];

        try {
            if (empty($from)) {
                $dbVersion = $db->_qf_version->findOne(array('_id' => $className::getCollectionName()));
                if (!empty($dbVersion['version'])) {
                    $from = $dbVersion['version'];
                } else {
                    $from = 0;
                }
            }
            if (empty($to)) {
                $to = $className::getMaxDatabaseVersion();
            }
            $status = $className::install($db, $from, $to);
            if ($status !== true && $status !== null) {
                return 'An error occurred: ' . $status;
            } else {
                $db->_qf_version->update(array('_id' => $className::getCollectionName()), array('_id' => $className::getCollectionName(), 'version' => $to), array('upsert' => true));
                return 'Entity ' . $parameter['entity'] . ' was installed successfully' . ($from ? ' from ' . $from : '') . ($to ? ' to ' . $to : '') . '!';
            }
        } catch (Exception $e) {
            return 'An error occurred: ' . $e->getMessage();
        }
    }
    
    public function uninstall($parameter, $c)
    {
        if (!$parameter['entity']) {
            return 'Error: no Entity given';
        }

        if (!class_exists($parameter['entity'])) {
            return 'Error: Class ' . $parameter['entity'] . ' does not exist';
        }
        
        if (!($parameter['entity'] instanceof \QF\DB\Entity)) {
            return 'Error: Class ' . $parameter['entity'] . ' is not an \\QF\\DB\\Entity';
        }

        $type = $parameter['storageKey'];

        $db = $c[$type]->get();


        $className = $parameter['entity'];

        $from = $parameter['from'];
        $to = $parameter['to'];

        $res = $db->query('SHOW TABLES LIKE "_qf_version"');
        $row = $res->fetch_assoc();
        if (empty($row)) {
            $db->query('CREATE TABLE _qf_version (id VARCHAR(255), version INT(11), PRIMARY KEY (id)) ENGINE=INNODB DEFAULT CHARSET=utf8');
        }
        
        try {
            if (empty($from)) {
                $res = $qb->query('SELECT version FROM _qf_version WHERE id = "'.$className::getTableName().'"');
                $row = $res->fetch_assoc();
                if (!empty($row['version'])) {
                    $from = $row['version'];
                } else {
                    $from = 0;
                }
            }

            if (empty($to)) {
                $to = 0;
            }
            $status = $className::uninstall($db, $from, $to);
            if ($status !== true && $status !== null) {
                return 'An error occurred: ' . $status;
            } else {
                $res = $qb->query('SELECT version FROM _qf_version WHERE id = "'.$className::getTableName().'"');
                $row = $res->fetch_assoc();
                if (!empty($row['version'])) {
                    $qb->query('UPDATE _qf_version SET version = "'.$to.'" WHERE id = "'.$className::getTableName().'"');
                } else {
                    $qb->query('INSERT INTO _qf_version SET id = "'.$className::getTableName().'", version = "'.$to.'"');
                }
                return 'Entity ' . $parameter['entity'] . ' was uninstalled successfully' . ($from ? ' from ' . $from : '') . ($to ? ' to ' . $to : '') . '!';
            }
        } catch (Exception $e) {
            return 'An error occurred: ' . $e->getMessage();
        }
        
    }
    
    public function uninstallMongo($parameter, $c)
    {
        if (!$parameter['entity']) {
            return 'Error: no Entity given';
        }

        if (!class_exists($parameter['entity'])) {
            return 'Error: Class ' . $parameter['entity'] . ' does not exist';
        }

        if (!($parameter['entity'] instanceof \QF\Mongo\Entity)) {
            return 'Error: Class ' . $parameter['entity'] . ' is not an \\QF\\Mongo\\Entity';
        }
        
        $type = $parameter['storageKey'];

        $db = $c[$type]->get();

        $className = $parameter['entity'];

        $from = $parameter['from'];
        $to = $parameter['to'];

        try {
            if (empty($from)) {
                $dbVersion = $db->_qf_version->findOne(array('_id' => $className::getCollectionName()));
                if (!empty($dbVersion['version'])) {
                    $from = $dbVersion['version'];
                } else {
                    $from = 0;
                }
            }

            if (empty($to)) {
                $to = 0;
            }
            
            $status = $className::uninstall($db, $from, $to);
            if ($status !== true && $status !== null) {
                return 'An error occurred: ' . $status;
            } else {
                $db->_qf_version->update(array('_id' => $className::getCollectionName()), array('_id' => $className::getCollectionName(), 'version' => $to), array('upsert' => true));
                return 'Entity ' . $parameter['entity'] . ' was uninstalled successfully' . ($from ? ' from ' . $from : '') . ($to ? ' to ' . $to : '') . '!';
            }
        } catch (Exception $e) {
            return 'An error occurred: ' . $e->getMessage();
        }
        
    }

}
