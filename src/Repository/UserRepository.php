<?php
// src/Repository/UserRepository.php
/**
 * Some extra functions for locating and processing Users
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use App\Kernel;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{

    public function __construct(
        ManagerRegistry $registry,
        private Kernel $kernel,
        private ContainerBagInterface $params,
    ){
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * JD not sure yet how and when this kicks in :(
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of <%s> are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Returns list of users with this role
     *
     * I couldn't work out how to use isGranted for an arbitrary user,
     * Maybe possible using the AccessDecisionManager and a SecurityVoter
     *
     * But for the moment I'll just check that a role is assign,
     * Therefore this DOES NOT respect any role hierarchy
     *
     * @param string $role role must be an exact match for a role_name
     * @return array
     *
     */
    public function findByRole(string $role): array
    {
        $valid_roles = $this->kernel->getContainer()->getParameter('security.role_hierarchy.roles');

        if (!key_exists($role,$valid_roles)){
            throw new UnsupportedUserException(sprintf('Unrecognised role <%s>.', $role));
        }

        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', "%$role%")
            ->getQuery()
            ->getResult();
    }

    /**
     * We could have just scanned the result of a ->findAll and moved the admin row
     * However I wanted examples of DQL and ORM queries
     *
     * SQLITE and Doctrine do not have any IF statements, so use CASE statements
     *
     * @return User[]
     */
    public function findAllWithAdminFirst(): array
    {
        // We need to set the temporary isAdmin column to hidden
        // otherwise we will get an array of arrays instead of an array of objects
        // ie [[user,isAdmin],] instead of [user,]

        // DQL
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT u,
            CASE WHEN u.roles LIKE :role THEN 0 ELSE 1 END AS HIDDEN isAdmin
            FROM App\Entity\User u
            ORDER BY isAdmin, u.id ASC'
        )->setParameter('role', '%ROLE_ADMIN%');
        $result = $query->getResult();

        // DBAL
        /*$result = $this->createQueryBuilder('u')
        ->addSelect('CASE WHEN u.roles LIKE :role THEN 0 ELSE 1 END AS HIDDEN isAdmin')
        ->orderBy('isAdmin','ASC')
        ->addOrderBy('u.id', 'ASC')
        ->setParameter('role','%ROLE_ADMIN%')
        ->getQuery()
        ->getResult();*/

        return $result;
    }

    /**
     * Remove all admin users, this is used by RecreateAdminUserCommand
     * and auto recreation of public admin
     *
     * @return void
     */
    public function deleteAdmins():void
    {
        $qb = $this->createQueryBuilder('u')
            ->delete(User::class,'u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->execute();
    }

    /**
     *  In this public showcase the admin gets recreated when auto deleted.
     *  This is also used in tests.
     *
     * @return User
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function createPublicAdmin():User
    {
        $this->deleteAdmins();
        $user = new User();
        $user
            ->setName($this->params->get('app.public_admin_name'))
            ->setPlainPassword($this->params->get('app.public_admin_password'))
            ->setRoles(['ROLE_ADMIN']);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        return $user;
    }

}
