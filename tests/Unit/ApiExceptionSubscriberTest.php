<?php

namespace App\Tests\Unit;

use App\EventSubscriber\ApiExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiExceptionSubscriberTest extends TestCase
{
    public function testApiExceptionIsConvertedToJsonResponse(): void
    {
        $subscriber = new ApiExceptionSubscriber(new NullLogger(), false);

        $kernel = $this->createMock(KernelInterface::class);
        $request = Request::create('/api/test');
        $exception = new NotFoundHttpException('Not found');

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertStringContainsString('request_error', $response->getContent());
    }

    public function testNonApiExceptionIsIgnored(): void
    {
        $subscriber = new ApiExceptionSubscriber(new NullLogger(), false);

        $kernel = $this->createMock(KernelInterface::class);
        $request = Request::create('/profile');
        $exception = new \RuntimeException('Fail');

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $subscriber->onKernelException($event);

        self::assertNull($event->getResponse());
    }
}
