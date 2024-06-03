<?php
// src/Twig/AppExtension.php
/**
 * An example of extending Twig with our own functions
 *
 *  Adds a simple appButton function with label/route/datavalue options
 *  Adds a slick sliding on/off toggle to replace the default checkbox
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Psr\Log\LoggerInterface;

class OurTwigExtension extends AbstractExtension
{
    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $router,
        private LoggerInterface $logger
    ) {
    }

    /**
     * These are our custom twig functions to create a Twig fragments.
     *
     * We are using the default services.yaml configuration so these will auto register.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('appButton',[$this,'appButton'],['is_safe' => ['html']]),
            new TwigFunction('slidingCheckbox', [$this,'slidingCheckbox'],['is_safe' => ['html']]),
        ];
    }

    /**
     * A button
     *
     * An options array could be included, with routeParameters, hints, roles, classes, attrs ...
     * However, this starts to move data into the twig layer, to keep it light I've only allowed a single data attribute
     *
     * @param string $label
     * @param string $route
     * @param array $options
     * @return string
     *
     */
    public function appButton(string $label, string $route, string $data = '', string $classes = ''): string
    {

        if (!empty($route)) {
            // we could allow this to throw the exception, but using placeholder buttons, so log the error for debug
            try {
                $url = $this->router->generate($route, []);
            } catch (RouteNotFoundException $e) {
                $this->logger->error("Unknown route name passed to menuButton Twig Extension", ['route' => $route]);
            }
        }

        return $this->twig->render('fragments/_appButton.html.twig', [
                'url' =>$url??'',
                'label' => $label??'No Label',
                'data' => $data,
                'classes' => $classes
            ]);
    }


    /**
     * Our Sliding checkbox replacement for the standard html checkbox
     *
     * @param string $idname
     * @return string
     *
     */
    public function slidingCheckbox(string $idname): string
    {
        return $this->twig->render('fragments/_sliding-checkbox.html.twig', [
            'idname' =>$idname??''
        ]);
    }

}