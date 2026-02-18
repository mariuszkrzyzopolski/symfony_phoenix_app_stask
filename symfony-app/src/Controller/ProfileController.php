<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Photo;
use App\Service\PhoenixApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function profile(
        Request $request, 
        EntityManagerInterface $em,
        PhoenixApiService $phoenixApiService
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
            
            if (empty($phoenixToken)) {
                $this->addFlash('error', 'API access token is required');
            } elseif (strlen($phoenixToken) > 1000) {
                $this->addFlash('error', 'API access token is too long');
            } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $phoenixToken)) {
                $this->addFlash('error', 'API access token contains invalid characters');
            } else {
                $user->setPhoenixAccessToken($phoenixToken);
                
                try {
                    $result = $phoenixApiService->importPhotos($phoenixToken);
                    
                    if ($result['success']) {
                        foreach ($result['photos'] as $photoData) {
                            $existingPhoto = $em->getRepository(Photo::class)->findOneBy([
                                'user' => $user,
                                'imageUrl' => $photoData['photo_url']
                            ]);
                            
                            if (!$existingPhoto) {
                                $photoUrl = $photoData['photo_url'];
                                
                                if (filter_var($photoUrl, FILTER_VALIDATE_URL) && strlen($photoUrl) <= 2048) {
                                    $photo = new Photo();
                                    $photo->setImageUrl($photoUrl);
                                    $photo->setUser($user);
                                    $em->persist($photo);
                                }
                            }
                        }
                        
                        $em->flush();
                        
                        if (isset($result['message'])) {
                            $this->addFlash('success', $result['message']);
                        } else {
                            $this->addFlash('success', 'Photos imported successfully');
                        }
                    } else {
                        $this->addFlash('error', $result['error']);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to import photos: ' . $e->getMessage());
                }
            }
            
            return $this->redirectToRoute('profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}
