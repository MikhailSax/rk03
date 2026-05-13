<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/media', name: 'api_media_')]
class MediaProxyController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    #[Route('/cloud-image', name: 'cloud_image', methods: ['GET'])]
    public function cloudImage(Request $request): Response
    {
        $source = trim((string) $request->query->get('url', ''));
        if ($source === '') {
            return new Response('Missing "url" query parameter.', Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('#^https?://cloud\.mail\.ru/public/#i', $source)) {
            return new Response('Only cloud.mail.ru public links are supported.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $html = $this->httpClient->request('GET', $source, [
                'max_redirects' => 5,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; DominoOutDoor/1.0; +https://domline.ru)',
                ],
            ])->getContent();
        } catch (\Throwable) {
            return new Response('Unable to fetch source page.', Response::HTTP_BAD_GATEWAY);
        }

        $imageUrl = $this->extractOgImage($html);
        if ($imageUrl === null) {
            return new Response('Image URL not found in source page.', Response::HTTP_NOT_FOUND);
        }

        return new RedirectResponse($imageUrl, Response::HTTP_FOUND);
    }

    private function extractOgImage(string $html): ?string
    {
        if (preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i', $html, $matches) === 1) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        if (preg_match('/<meta[^>]+content="([^"]+)"[^>]+property="og:image"/i', $html, $matches) === 1) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        return null;
    }
}
