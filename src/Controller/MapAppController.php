<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MapAppController extends AbstractController
{
    #[Route('/map/app', name: 'app_map_app')]
    public function index(): Response
    {
        return $this->render('map_app/index.html.twig', [
            'controller_name' => 'MapAppController',
        ]);
    }
}
