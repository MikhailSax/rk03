<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiRequestGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%env(default::APP_BASE_URL)%')]
        private readonly ?string $appBaseUrl = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if ($request->isMethod('OPTIONS')) {
            $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT));

            return;
        }

        $origin = $request->headers->get('Origin');
        $referer = $request->headers->get('Referer');
        $baseHost = $this->extractHost($this->appBaseUrl);

        $allowedHosts = array_values(array_unique(array_filter([
            $baseHost,
            $request->getHost(),
        ])));

        if ($allowedHosts === []) {
            return;
        }

        $originHost = $this->extractHost($origin);
        if ($originHost !== null && !in_array($originHost, $allowedHosts, true)) {
            $event->setResponse(new JsonResponse([
                'error' => 'forbidden_origin',
                'message' => 'Доступ к API разрешён только с основного сайта.',
            ], JsonResponse::HTTP_FORBIDDEN));

            return;
        }

        if ($originHost === null && $referer !== null) {
            $refererHost = $this->extractHost($referer);
            if ($refererHost !== null && !in_array($refererHost, $allowedHosts, true)) {
                $event->setResponse(new JsonResponse([
                    'error' => 'forbidden_referer',
                    'message' => 'Доступ к API разрешён только с основного сайта.',
                ], JsonResponse::HTTP_FORBIDDEN));
            }
        }
    }

    private function extractHost(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }
}
