<?php
// src/Cpm/Security/UserProvider.php
namespace Cpm\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\DBAL\Connection;
use Cpm\UserDb;

class UserProvider implements UserProviderInterface
{
    private $conn;
    private $userDb;

    public function __construct (Connection $conn)
    {
        $this->conn = $conn;
        $this->userDb = new UserDb($conn);
    }

    public function loadUserByUsername($username)
    {
        // make a call to your DB here
        $user = $this->userDb->getUserByName($username);
        if ($user) { 
            return $user;
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function addUser(UserInterface &$user)
    {
        $user->setId($this->userDb->addUser($user));
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
    }
}
?>
