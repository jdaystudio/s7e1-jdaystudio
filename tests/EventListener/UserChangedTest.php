<?php
// src/tests/EventListener/UserChangedTest.php
/**
 * Test to confirm UserChanged event is acted upon.
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Tests\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserChangedTest extends KernelTestCase
{
    // cant auto-wire here
    private ?EntityManager $em;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        // cant use a mock for this as we need the listener to trigger
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $this->em->beginTransaction();
    }

    public function testProcess(): void
    {
        /* @var User $user */
        $user = new User();
        $user->setName("test")
            ->setPlainPassword("12Qa34@!Oa-1");
        $this->em->persist($user);
        $this->em->flush();

        $this->assertTrue( in_array('ROLE_USER',$user->getRoles()),'All Users should have a default ROLE_USER');
        $this->assertTrue( is_null($user->getPlainPassword()),'User plain password should have been erased on save');
        $this->assertFalse( is_null($user->getPassword()), 'User password should have been hashed on save');
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        $this->em->close();
        $this->em = null;

        parent::tearDown();
    }

}