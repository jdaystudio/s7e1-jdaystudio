<?php
// src/tests/Entity/UserTest.php
/**
 * User entity comparison testing
 * And required / expected fields are available
 *
 * @author John Day jdayworkplace@gmail.com
 */
use PHPUnit\Framework\TestCase;
use App\Entity\User;

final class UserTest extends TestCase
{
    public function testUserIsEqualTo():void{

        $userA = new User();
        $userB = new User();

        // the function should report false if no SID set (auto eject mechanism)
        $this->assertFalse($userA->isEqualTo($userB),"User A should report false if no SID set");

        // NOTE: sids could not easily be validated
        $userA->setSid('IMLOGGEDIN');
        $this->assertTrue($userA->isEqualTo($userB),"User A should report true if users are same (excluding sid)");

        $userA->setName("ab");
        $this->assertFalse($userA->isEqualTo($userB),"User A should report false to empty UserB");

        $userB->setName("b");
        $this->assertFalse($userA->isEqualTo($userB),"User A should report false to UserB name difference");

        $userB->setName("ab");
        $this->assertTrue($userA->isEqualTo($userB),"User A should report equal to UserB name");

        $userA->setRoles(['ROLE_1','ROLE_2']);
        $this->assertFalse($userA->isEqualTo($userB),"User A should report false to UserB roles difference");

        $userB->setRoles(['ROLE_1']);
        $this->assertFalse($userA->isEqualTo($userB),"User A should report false to UserB roles difference");

        $userB->setRoles(['ROLE_1','ROLE_2']);
        $this->assertTrue($userA->isEqualTo($userB),"User A should report equal to UserB roles");
    }

    /**
     * Checking fields are as expected
     * (could also check for setters/getters but skipping that)
     * @return void
     */
    public function testUserFieldsExist()
    {
        $properties = [
            'id','created','lastLoginAt','password','roles','name','plainpassword'
        ];
        foreach($properties as $property){
            $this->assertTrue(property_exists(User::class,$property),"User id $property missing");
        }
    }
}
