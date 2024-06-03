<?php
// src/Controller/LoginController.php
/**
 * A public facing (unauthorized), Symfony 'auto magic' user log-in form
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(): Response
    {
        // this is a silent log-in form
        // $error = $authenticationUtils->getLastAuthenticationError();
        return $this->render('login/index.html.twig');
    }

}
