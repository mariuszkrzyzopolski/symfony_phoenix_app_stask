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
                $this->addFlash('error', 'Phoenix API token is required');
            } else {
                // Save token to user
                $user->setPhoenixAccessToken($phoenixToken);
                
                try {
                    // Import photos from Phoenix API
                    $result = $phoenixApiService->importPhotos($phoenixToken);
                    
                    if ($result['success']) {
                        // Create Photo entities for imported photos
                        foreach ($result['photos'] as $photoData) {
                            // Check if photo already exists to prevent duplicates
                            $existingPhoto = $em->getRepository(Photo::class)->findOneBy([
                                'user' => $user,
                                'imageUrl' => $photoData['photo_url']
                            ]);
                            
                            if (!$existingPhoto) {
                                $photo = new Photo();
                                $photo->setImageUrl($photoData['photo_url']);
                                $photo->setUser($user);
                                $em->persist($photo);
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
