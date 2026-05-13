<?php

// src/Controller/OAuthController.php
namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class OauthController extends AbstractController
{
    #[Route('/connect/yandex', name: 'connect_yandex')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Redirect to Yandex
        return $clientRegistry
            ->getClient('yandex') // defined in config/packages/knp_oauth2_client.yaml
            ->redirect([], []);
    }

    #[Route('/connect/yandex/check', name: 'connect_yandex_check')]
    public function connectCheckAction(): RedirectResponse
    {
        // This route will be handled by the Symfony Authenticator (see below)
        // or you can retrieve user data here if not using an authenticator.
        return $this->redirectToRoute('app_homepage'); // Redirect after successful login
    }
}
