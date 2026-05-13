<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdvertisementPublicController extends AbstractController
{
    #[Route('/catalog/construction/{id}', name: 'app_catalog_construction', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        return $this->render('advertisement/public_show.html.twig', [
            'id' => $id,
        ]);
    }
}
