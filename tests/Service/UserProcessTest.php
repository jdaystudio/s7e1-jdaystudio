<?php
// tests/Service/UserProcessTest.php
/**
 * Some Integration tests for the UserProcess service
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserProcess;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserProcessTest extends KernelTestCase
{
    private ContainerInterface $container;
    private UserProcess $up;
    private ?EntityManager $em;

    // don't think we can auto-wire here
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->container = $kernel->getContainer();

        // cant use a mock for this as we need the listener to trigger
        $this->em = $this->container->get('doctrine')->getManager();
        $this->em->beginTransaction();

        $container = static::getContainer();
        $this->up = $container->get(UserProcess::class);
        $this->up->setCheckRemoteLogout(false);
    }

    /**
     * Testing the process return for the auto logout auto delete
     * DOES NOT test actual login/logout of users
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function testProcessBasicUser(): void
    {
        /* @var User $user */
        $user = new User();
        $user->setName("test")
            ->setPlainPassword("12Qa34@!Oa-1")
            ->setSid("testsessionid");

        $this->setUserLastLogin($user);

        $result = $this->up->process($user->getId());
        $this->assertTrue(is_array($result), "process should return an array");
        $this->assertTrue( $user->getId() == $result['id'], "result id should match");
        $this->assertTrue($this->up::PENDING_LOG_OUT_LOCAL == $result['state'], "result state should be PENDING_LOG_OUT_LOCAL");

        $auto_logout = $this->container->getParameter("app.auto_logout_seconds");
        $this->setUserLastLogin($user,"PT".($auto_logout + 2)."S");

        $result = $this->up->process($user->getId());
        $this->assertTrue($this->up::PENDING_DELETE == $result['state'], "result state should be PENDING_DELETE");

        $auto_delete = $this->container->getParameter("app.auto_delete_seconds");
        $this->setUserLastLogin($user,"PT".($auto_logout + $auto_delete + 2)."S");

        $oldUID = $user->getId();
        $result = $this->up->process($user->getId());
        $this->assertTrue($this->up::DELETED == $result['state'], "result state should be DELETED");

        $userExists = $this->em->getRepository(User::class)->find($oldUID);
        $this->assertTrue(null == $userExists,"User should no longer exist");
    }

    /**
     * Testing Public Admin behavior
     * the public admin in this application should be deleted and recreated
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function testProcessPublicAdminUser(): void
    {
        $user = $this->em->getRepository(User::class)->createPublicAdmin();
        $this->assertTrue(null != $user, "Failed to create public admin");
        $this->assertTrue($user->hasRole('ROLE_ADMIN'), "public admin missing ROLE_ADMIN");

        $auto_logout = $this->container->getParameter("app.auto_logout_seconds");
        $auto_delete = $this->container->getParameter("app.auto_delete_seconds");
        $this->setUserLastLogin($user,"PT".($auto_logout + $auto_delete + 2)."S");

        $oldUID = $user->getId();
        $result = $this->up->process($user->getId());
        $this->assertTrue($this->up::DELETED == $result['state'], "result should be DELETED");
        $this->assertTrue($oldUID != $result['id'], "new admin should have different id");

        $userExists = $this->em->getRepository(User::class)->find($oldUID);
        $this->assertTrue(null == $userExists,"User should no longer exist");

        // new admin should have been created
        $admins = $this->em->getRepository(User::class)->findByRole('ROLE_ADMIN');
        $this->assertTrue(1 == count($admins),"One admin user should have been recreated");
    }

    /**
     * Set last login time with optional offset
     *
     * @param User $user
     * @param string|null $intervalString
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function setUserLastLogin(User $user, ?string $intervalString = null):void
    {
        $lastLogIn = $this->getDateTime($intervalString);
        $user->setLastLoginAt($lastLogIn);
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Get date with optional interval
     *
     * @param string|null $intervalString
     * @return \DateInterval now | now - time period
     * @throws \Exception
     */
    private function getDateTime(?string $intervalString):\DateTimeImmutable
    {
        $result = new \DateTimeImmutable();
        if ($intervalString) {
            $period = new \DateInterval($intervalString);
            $result = $result->sub($period);
        }
        return $result;
    }


    protected function tearDown(): void
    {
        $this->em->rollback();
        $this->em->close();
        $this->em = null;

        parent::tearDown();
    }

}