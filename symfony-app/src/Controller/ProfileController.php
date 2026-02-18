<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\PhotoImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    public function __construct(
        private PhotoImportService $photoImportService
    ) {}

    #[Route('/profile', name: 'profile')]
    public function profile(
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('home');
        }

        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            $session->clear();
            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $phoenixToken = $request->request->get('phoenix_access_token');
            
            $validation = $this->photoImportService->validateToken($phoenixToken);
            
            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
            } else {
                $user->setPhoenixAccessToken($phoenixToken);
                $result = $this->photoImportService->importPhotosFromPhoenix($phoenixToken, $user);
                
                if ($result['success']) {
                    $this->addFlash('success', $result['message']);
                } else {
                    $this->addFlash('error', $result['error']);
                }
            }
            
            return $this->redirectToRoute('profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}
