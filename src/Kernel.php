<?php
// src/Kernel.php
/**
 *  Defined a compiler pass to disable re-setting / re-booting test kernel, so we can follow through on the tests
 *  @see https://symfony.com/doc/current/testing.html#application-tests
 *
 *  Usually the test kernel gets reset at the start of each request
 *  this code clears the reset(able) flag on the modules that we require NOT to reset between requests
 *  when running in the 'test' environment.
 *
 *  this allows multiple requests without the framework re-setting,
 *  very useful @see /src/tests/Security/AuthenticationTest.php
 *  we must also call the following > client->disableReboot() < in each test where this is required.
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function process(ContainerBuilder $container): void
    {
        if ('test' === $this->environment) {
            // prevents the security token being cleared
            $container->getDefinition('security.token_storage')->clearTag('kernel.reset');

            // prevents Doctrine entities from being detached
            $container->getDefinition('doctrine')->clearTag('kernel.reset');
        }
    }
}
