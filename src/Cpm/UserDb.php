<?php
namespace Cpm;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\DBALException;
use Cpm\Security\User;

class UserDb {

    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    function getUserByName($username)
    {
        $sql = 'select * from users_summary where username = ?';
        $userData = array();
        try {
            $rows = $this->conn->fetchAll($sql, array($username));
            if(!$rows){
                return NULL;
            }

            $roles = array();
            $projects = array();
            foreach ($rows as $r) { 
                $roles[] = $r['role']==NULL ? '':$r['role'];
                $projects[] = $r['project']==NULL ? '':$r['project'];
            }
            $userData = $rows[0];
            $userData['roles'] = $roles;
            $userData['projects'] = $projects;

            return new User($userData);

        }catch(DBALException $e) {
            $this->rx_error_log( "Error: ".$e, 3 ,'php-errors.log');
            die( "Error: ".$e);
        }

        return NULL;
    }

    /**
     * create new user and return the user id
     */
    function addUser(UserInterface $user)
    {
        $id = '';
        $userData = $user->getUserDbArray();
        try{
            $this->conn->insert('users', $userData);
            $id = $this->conn->lastInsertId();
        } catch(DBALException $e){
            $this->rx_error_log( "Error: ".$e, 3 ,'php-errors.log');
            die( "Error: ".$e);
        }
        return $id;
    }

    function rx_error_log($msg)
    {
        if (is_array($msg)) {
            $msg = print_r($msg,1); 
        }
        error_log($msg . "\n", 3, 'php-errors.log');
    }
}
?>
