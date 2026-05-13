<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StaticPagesController extends AbstractController
{
    #[Route('/static/pages', name: 'app_static_pages')]
    public function index(): Response
    {
        return $this->render('static_pages/index.html.twig', [
            'controller_name' => 'StaticPagesController',
        ]);
    }
}
