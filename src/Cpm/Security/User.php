<?php 
// src/Cpm/Security/User.php
namespace Cpm\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Cpm\Db;
class User implements UserInterface, EquatableInterface
{
    private $username;
    private $password;
    private $salt;
    private $roles;

    public function __construct($username, $password, $salt, array $roles)
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
    }
    function _getSalt() {
       return mt_rand(92444444444);
    }
    public function createUser($username, $password, $email, array $roles = array())
    {
        // find the encoder for a UserInterface instance
        $encoder = $app['security.encoder_factory']->getEncoder($user);
        $salt = getSalt();
        // compute the encoded password for foo
        $password = $encoder->encodePassword($password, $salt);
        if (count($roles) == 0 ) { 
            $roles[] = 2; // todo get from db 'authenticated';
        }
        //$sql = "insert into users ('username', 'password', 'salt', 'email') Values (?,?,?,?)"; 
        $db = new Db; 
        $userData = array('username'=>$username, 'password'=>$password, 'salt'=>$salt, 'email'=>$email);
        $id = $db->dbConn()->insert('users', $userData);
        $userData['id'] = $id;
        $userData['roles'] = $roles;
        return $userData;
    }
    
    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
?>