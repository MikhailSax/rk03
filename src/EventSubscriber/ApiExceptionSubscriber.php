<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        $this->logger->error('API exception', [
            'path' => $path,
            'status_code' => $statusCode,
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);

        $payload = [
            'error' => 'internal_error',
            'message' => 'Произошла ошибка при обработке запроса.',
        ];

        if ($this->debug) {
            $payload['debug'] = $exception->getMessage();
        }

        if ($exception instanceof HttpExceptionInterface && $statusCode < 500) {
            $payload['error'] = 'request_error';
            $payload['message'] = $exception->getMessage();
        }

        $event->setResponse(new JsonResponse($payload, $statusCode));
    }
}
