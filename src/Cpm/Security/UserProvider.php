<?php
// src/Cpm/Security/UserProvider.php
namespace Cpm\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Cpm\Db;
use \PDO;
class UserProvider implements UserProviderInterface
{
    public function loadUserByUsername($username)
    {
        // make a call to your DB here
        $db = new Db();
        $rows = $db->dbQuery('cpmv', "Select * from users_summary where username = ?", array($username));
        if ($rows) { 
            $roles = array();
            foreach ($rows as $r) { 
                $roles[] = $r['role'];
            }
            $r = $rows[0];
            return new User($r['username'], $r['password'], $r['salt'], $roles);
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Cpm\Security\User';
        //return $class === 'AppBundle\Security\User\User';
    }
}
?>