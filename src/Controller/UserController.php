<?php
// src/Controller/UserController.php
/**
 * User profile and managing users
 *
 * @author John Day jdayworkplace@gmail.com
 */
namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class UserController extends AbstractController
{
    /**
     * List users and available actions
     *
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/admin/users', name: 'users')]
    public function index(UserRepository $userRepository, ContainerBagInterface $params): Response
    {
        $users = $userRepository->findAllWithAdminFirst();

        return $this->render('user/list.html.twig', [
            'users' => $users,
            'total_allowed' => 1 + $params->get('app.max_users_allowed')
        ]);
    }

    /**
     *  Create a new user, using a UserType Form
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/admin/user/new', name:'user-new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // use the submitted values
            $user = $form->getData();
            // tell doctrine we want to store this entity
            // create database queries (and run our pre-persist listener)
            $entityManager->persist($user);
            // run database queries
            $entityManager->flush();
            return $this->redirectToRoute('users');
        }

        return $this->render('user/new.html.twig', [
            'user_form' => $form,
        ]);
    }

    /**
     * Updates current username and/or password
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/profile', name:'profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sessionUser = parent::getUser();

        $userRepository = $entityManager->getRepository(User::class);

        /* @var User $user */
        $user = $userRepository->find($sessionUser->getId());

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // use the submitted values
            $user = $form->getData();
            // doctrine is watching so we can just
            $entityManager->flush();
            return $this->redirectToRoute('_logout_main');
        }

        return $this->render('user/profile.html.twig', [
            'user_form' => $form,
        ]);
    }

    /**
     * Endpoint to handle REST call to delete user
     *
     * Allowing null id to simplify route in javascript
     *
     * @param EntityManagerInterface $entityManager
     * @param ?int $id
     * @return Response Status code 200 OK | 400 Failed
     */
    #[Route('/admin/user/delete/{id<\d+>?0}', name: 'user-delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $entityManager, ?int $id): Response
    {
        $response = new Response();

        $userRepository = $entityManager->getRepository(User::class);

        if ($user = $userRepository->find($id)){
            $entityManager->remove($user);
            $entityManager->flush();
            $response->setStatusCode(200);
        }else{
            $response->setStatusCode(422);
        }

        return $response;
    }
}
