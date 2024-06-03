<?php
// src/Controller/StatusController.php

/**
 * Routes for the public and private live status JSON endpoints
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\UserProcess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class StatusController extends AbstractController
{
    /**
     * Public endpoint for admin status
     *
     * @param Request $request
     * @param UserProcess $userProcess
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route('/public/admin/status', name: 'admin-status', methods: ['GET'])]
    public function processPublicAdmin(Request $request, UserProcess $userProcess, UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findByRole('ROLE_ADMIN');

        // set a placeholder, in case this catches the admin being reset
        empty($users) ? $admin_id = 0 : $admin_id = $users[0]->getId();

        $result = $userProcess->process($admin_id);

        unset($result['id']); // hide admin id in this public call

        return $this->json($result);
    }

    /**
     * Endpoint to process and perform auto eject and/or auto delete
     * Used for live feedback of status/timeouts in front end
     *
     * Allowing null id to simplify route in javascript
     * Sending an invalid user id will return 0 values (silent error for simplicity in this example)
     *
     * We could have a bulk version of this, but not really required for this example project
     *
     * @param UserProcess $userProcess
     * @param ?int $id
     * @return JsonResponse returns status in json format @see UserProcess
     */
    #[Route('/api/user/status/{id<\d+>?0}', name: 'user-status', methods: ['GET'])]
    public function process(Request $request, UserProcess $userProcess, ?int $id): JsonResponse
    {
        return $this->json($userProcess->process($id));
    }

}
