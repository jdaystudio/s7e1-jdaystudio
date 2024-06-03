<?php
// src/tests/Controller/UserControllerTest.php
/**
 * Simple check that redirect works for the admin/users page
 * @see /src/tests/SessionHelper.php
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Tests\Web;

use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testRedirectToLogin(): void
    {
        $client = $this->ourClient();

        $client->request('GET','/admin/users',[],[], $this->localServer());
        $response = $client->getResponse();

        $this->assertTrue("302" == $response->getStatusCode(), "Should return as redirect");
        $this->assertTrue("/login" == $response->getTargetUrl(), "Should redirect to login page");

        $client->followRedirect();

        $this->assertResponseIsSuccessful("Should have reached valid page");
        $this->assertSelectorTextContains('title','Login', "Should have reached Login page");
    }

}
