<?php
namespace QF;

class DB
{
    /**
     * @var \QF\Core
     */
    protected $qf = null;
    protected $connections = array();

    /**
     * initializes a PDO object as configured in $qf_config['db']
     *
     * $qf_config['db'] must be an array of arrays with the following elements:
     * 'driver' => 'mysql:host=localhost;dbname=qfdb', //a valid PDO dsn. @see http://de3.php.net/manual/de/pdo.construct.php
     * 'username' => 'root', //The user name for the DSN string. This parameter is optional for some PDO drivers.
     * 'password' => '', //The password for the DSN string. This parameter is optional for some PDO drivers.
     * 'options' => array() //A key=>value array of driver-specific connection options. (optional)
     *
     */
    public function __construct(qfCore $qf)
    {
        $this->qf = $qf;
    }

    /**
     * initializes and returns a db connection as configured in $qf_config['db'][$connection]
     * @return PDO the database instance
     */
    function get($connection = 'default')
    {
        if (!isset($this->connections[$connection])) {
            $db = $this->qf->getConfig('db');
            if (is_array($db) && isset($db[$connection])) {
                $this->connections[$connection] = new PDO(
                    $db['driver'],
                    isset($db['username']) ? $db['username'] : '',
                    isset($db['username']) ? $db['password'] : '',
                    isset($db['options']) ? $db['options'] : array()
                );

                if ($this->connections[$connection] && $this->connections[$connection]->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
                    $this->connection->exec('SET CHARACTER SET utf8');
                }
            }
        }
        return $this->connections[$connection];
    }
}