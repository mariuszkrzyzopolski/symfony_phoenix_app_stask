<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HomeDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private HomeDataService $homeDataService
    ) {}

    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        $homeData = $this->homeDataService->getHomeData($request->query->all(), $userId);

        return $this->render('home/index.html.twig', $homeData);
    }
}
