<?php
// src/Controller/HomeController.php
/**
 * Homepage controller
 * Displays page wrapper components, Menus and Intro text
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // relying on access_control in config, and global twig variable <user>
        return $this->render('default/homepage.html.twig');
    }

}