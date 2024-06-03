<?php
// src/tests/SessionHelper.php
/**
 * A trait which can be used to test with our sessions.
 * This is slightly non-standard due to our single session behaviour,
 * and non-localhost setup. see remarks below.
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use function PHPUnit\Framework\isNull;

trait SessionHelper
{
    private ?AbstractBrowser $client = null;
    private ?EntityManager $em = null;

    /**
     * Create a KernelBrowser, and record client in our local client property
     *
     * Note: createClient invokes KernelTestCase::bootKernel(), and creates a "client" that is acting as the browser
     * @return AbstractBrowser
     */
    protected function ourClient():AbstractBrowser
    {
        if (isNull($this->client)){
            static::createClient([]);
            $this->client = $this->getClient();
        }
        return $this->client;
    }

    /**
     * I don't use localhost but a separate vhost per development project
     * So we need to tell symfony where to connect each time
     */
    protected function localServer():array
    {
        return [
            'HTTPS' => true,
            'HTTP_HOST' => 's7e1.lo.cal'
        ];
    }

    /**
     * Use this instead of the default basic client request, includes our server details
     *
     * @param string $method
     * @param string $uri
     * @return object
     */
    protected function ourRequest(string $method,string $uri):object
    {
        $this->client->request($method,$uri,[],[], $this->localServer());
        return $this->client->getResponse();
    }

    /**
     * Allow us to perform tests with an authenticated user
     * The setup / behaviour of our application causes some difficulty around this
     * also we don't want to progress to full end-to-end headless browser testing at the moment
     *
     * @param bool $admin
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function ourLoginUser(bool $admin):User
    {
        // rather than using a fixture just going to create the required user
        // because we are not 'logging in' but just setting up a valid auth
        // this will NOT trigger the "LoginSuccess" event
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->beginTransaction();

        if ($admin) {
            $user = $this->em->getRepository(User::class)->createPublicAdmin();
        }else{
            $user = new User();
            $user->setName("testuser")
            ->setPlainPassword("pw1234");
            $this->em->persist($user);
            $this->em->flush();
        }

        // https://github.com/symfony/symfony/discussions/46961
        // to get through authorization we need a valid token
        $tokenId = "testing_token";
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $tokenAttributes = [
            'name'=>SessionTokenStorage::SESSION_NAMESPACE . "/$tokenId",
            'value' => $csrfToken
        ];

        // Due to our requirement that the user must have
        // a known SID for our session matching / auto eject code,
        // we need a dummy sid to get through the login bootstrap
        $user->setSid("Temporary SID");
        $this->em->persist($user);
        $this->em->flush();

        // now request a login as usual
        $this->client->loginUser($user,'main',$tokenAttributes);

        // However, because we are not actually logging in, but just setting up a valid authentication
        // in this environment, it doesn't fire the "Login Success event",
        // so we need to manually record the new sid,
        // which allows our userIsEqual method to return true during authentication voting.
        $cookieJar = $this->client->getCookieJar();
        $cookie = $cookieJar->get('MOCKSESSID','/','s7e1.lo.cal');
        $sid = $cookie->getValue();
        $user->setSid($sid);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Undo any doctrine stuff we have done
     * @return void
     */
    protected function tidyUpDoctrine(){
        $this->em->rollback();
        $this->em->close();
        $this->em = null;
    }
}