<?php
//src/tests/Security/AuthenticationTest.php

/**
 * A couple of tests to confirm redirects and authentication access
 * @see /src/tests/SessionHelper.php for extra details
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Tests\Web;

use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthenticationTest extends WebTestCase
{
    use SessionHelper;

    /**
     * Check unauthenticated user access
     *
     * @return void
     */
    public function testRedirectToLogin(): void
    {
        $this->ourClient();

        $response = $this->ourRequest('GET','/');

        $this->assertTrue("302" == $response->getStatusCode(), "Should return as redirect");
        $this->assertTrue("/login" == $response->getTargetUrl(), "Should redirect to login page");

        $this->client->followRedirect();
        $response = $this->client->getResponse();

        $this->assertTrue("200" == $response->getStatusCode(), "Should have reached valid page");
        $this->assertSelectorTextContains('title','Login', "Should have reached Login page");
    }

    /**
     * Check admin user authorization
     *
     * This was quite difficult due to our 'special sauce' single session behaviour
     * AND not wanting to set up and perform full end to end, headless browser testing
     * (at least not in this first example project)
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAuthorizedAccess():void
    {
        $this->ourClient();
        $this->client->disableReboot();

        $user = $this->ourLoginUser(true);

        $this->ourRequest('GET','/profile');

        $this->assertResponseIsSuccessful("Should have been a allowed access to page");
        $this->assertSelectorTextContains('title','Profile', "Should have reached Profile page");

        $this->assertFormValue('form','user[name]',$user->getName(),"Profile user name should match");

        $this->ourRequest('GET','/admin/users');
        $this->assertResponseIsSuccessful("Should have been a allowed access to page");
        $this->assertSelectorTextContains('title','Users', "Should have reached Users page");

        $this->tidyUpDoctrine();
    }

    /**
     * Check standard user authorization
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testNonAuthorizedAccess(){
        $this->ourClient();
        $this->client->disableReboot();

        $user = $this->ourLoginUser(false);

        $this->ourRequest('GET','/profile');

        $this->assertResponseIsSuccessful("Should have been a allowed access to page");
        $this->assertSelectorTextContains('title','Profile', "Should have reached Profile page");

        $this->assertFormValue('form','user[name]',$user->getName(),"Profile user name should match");

        $response = $this->ourRequest('GET','/admin/users');
        $this->assertTrue("302" == $response->getStatusCode(), "Should be redirected");

        $this->client->followRedirect();
        $response = $this->client->getResponse();

        $this->assertTrue("200" == $response->getStatusCode(), "Should have reached valid page");
        $this->assertSelectorTextContains('title','Homepage', "Should have reached Homepage page");

        $this->tidyUpDoctrine();
    }
}
