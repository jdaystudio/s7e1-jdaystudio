<?php
//src/EventListener/UserChangedNotifier.php
/**
 * Maybe more of an exercise then best practice.
 * Mostly to have auto password hashing when ever a new password applied.
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
#[AsEntityListener(event: Events::preFlush, method: 'preFlush', entity: User::class)]
class UserChangedNotifier
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ){}

    /**
     * Only happens on first save
     *
     * @param User $user
     * @param PrePersistEventArgs $event
     * @return void
     */
    public function prePersist(User $user, PrePersistEventArgs $event): void
    {
        $user->setCreated(new \DateTimeImmutable());
    }

    /**
     * first thing to happen on flush (this seemed most reliable)
     * For fresh inserts this happens twice (with an additional prePersist event if you want it)
     * prePersist always triggered as expected but preUpdate didn't always trigger?
     * In the past I may have done this within the entity class, but that seems to be frowned on.
     */
    public function preFlush(User $user, PreFlushEventArgs $event): void
    {
        $this->sanitizeUser($user);
    }

    /**
     * Example of some auto follow up and possible sanitizing
     *
     * @param User $user
     * @return void
     */
    private function sanitizeUser(User $user): void
    {

        $user->guaranteeDefaultAndUniqueRoles();

        // if a plain password is set then auto hash it (based on the security.yaml config for the $user class)
        if ($plainpassword = $user->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $plainpassword
            );
            $user->setPassword($hashedPassword);
        }

        $user->eraseCredentials();
    }

}