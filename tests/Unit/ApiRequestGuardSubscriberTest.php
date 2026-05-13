<?php

namespace App\Tests\Unit;

use App\EventSubscriber\ApiRequestGuardSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ApiRequestGuardSubscriberTest extends TestCase
{
    public function testAllowsSameRequestHostEvenWhenBaseUrlHostIsDifferent(): void
    {
        $subscriber = new ApiRequestGuardSubscriber('https://bmsbur.ru');

        $kernel = $this->createMock(KernelInterface::class);
        $request = Request::create('http://127.0.0.1/api/advertisements');
        $request->headers->set('Origin', 'http://127.0.0.1:8000');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testBlocksForeignOrigin(): void
    {
        $subscriber = new ApiRequestGuardSubscriber('https://bmsbur.ru');

        $kernel = $this->createMock(KernelInterface::class);
        $request = Request::create('https://bmsbur.ru/api/advertisements');
        $request->headers->set('Origin', 'https://evil.example');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertStringContainsString('forbidden_origin', (string) $response->getContent());
    }
}
