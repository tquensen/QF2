<?php
namespace QF;

class User
{
    /**
     * @var \QF\Core
     */
    protected $qf = null;
    protected $roles = array();
    
    protected $user = null;
    protected $role = 'GUEST';

    public function __construct(Core $qf)
    {
        $this->qf = $qf;
        $this->roles = $qf->getConfig('roles', array());
        
        if (isset($_SESSION['_QF_USER'])) {
            $this->role = $_SESSION['_QF_USER']['role'];
            $this->user = $_SESSION['_QF_USER']['user'];
        }
    }
    
    public function login($role, $user = null, $persistent = false) {
        $this->setRole($role, $persistent);
        $this->setUser($user, $persistent);
    }
    
    public function logout() {
        $this->setRole('GUEST', true);
        $this->setUser(null, true);
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function getRole() {
        return $this->role;
    }
    
    public function setUser($user, $persistent = false) {
        $this->user = $user;
        if ($persistent) {
            $_SESSION['_QF_USER']['user'] = $user;
        }
    }

    public function setRole($role, $persistent = false) {
        $this->role = $role;
        if ($persistent) {
            $_SESSION['_QF_USER']['role'] = $role;
        }
    }
    
    /**
     *
     * @param string|array $rights the name of the right as string ('user', 'administrator', ..) or as array of rights
     * @return bool whether the current user has the required right or not / returns true if the right is 0
     */
    public function userHasRight($rights)
    {
        if (!$rights) {
            return true;
        }
        
        if (empty($this->roles[$this->role])) {
            return false;
        }
        
        return $this->checkRights($this->roles[$this->role], $rights);
    }
    
    protected function checkRights($givenRights, $requiredRights, $and = true)
    {
        if ($and) {
            foreach ((array)$requiredRights as $right) {
                if (is_array($right)) {
                    if (!$this->checkRights($givenRights, $right, !$and)) {
                        return false;
                    }
                }
                if (!in_array($right, (array) $givenRights)) {
                    return false;
                }
            }
            return true;
        } else {
            foreach ((array)$requiredRights as $right) {
                if (is_array($right)) {
                    if ($this->checkRights($givenRights, $right, !$and)) {
                        return true;
                    }
                }
                if (in_array($right, (array) $givenRights)) {
                    return true;
                }
            }
            return false;
        }
    }
}