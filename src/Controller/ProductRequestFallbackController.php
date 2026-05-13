<?php

namespace App\Controller;

use App\Entity\Advertisement;
use App\Entity\ProductRequest;
use App\Entity\User;
use App\Repository\AdvertisementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ProductRequestFallbackController extends AbstractController
{
    #[Route('/product-requests', name: 'product_requests_create_fallback', methods: ['POST'])]
    public function __invoke(
        Request                 $request,
        AdvertisementRepository $repository,
        EntityManagerInterface  $entityManager,
    ): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['message' => 'Некорректный формат запроса.'], 400);
        }

        $advertisementId = (int)($payload['advertisementId'] ?? 0);
        $sideCode = mb_strtoupper(trim((string)($payload['side'] ?? '')));
        $contactName = trim((string)($payload['contactName'] ?? ''));
        $contactPhone = trim((string)($payload['contactPhone'] ?? ''));
        $comment = isset($payload['comment']) ? trim((string)$payload['comment']) : null;

        $user = $this->getUser();
        if ($user instanceof User) {
            $fullName = trim(sprintf('%s %s', $user->getFirstName() ?? '', $user->getLastName() ?? ''));
            if ($fullName !== '') {
                $contactName = $fullName;
            }
            if (($user->getPhone() ?? '') !== '') {
                $contactPhone = (string)$user->getPhone();
            }
        }

        $honeypot = trim((string)($payload['website'] ?? ''));
        $formStartedAt = (int)($payload['formStartedAt'] ?? 0);

        if ($honeypot !== '') {
            return $this->json(['message' => 'Запрос отклонён.'], 422);
        }

        $nowMs = (int)floor(microtime(true) * 1000);
        if ($formStartedAt > 0 && $nowMs - $formStartedAt < 2500) {
            return $this->json(['message' => 'Пожалуйста, отправьте форму чуть позже.'], 429);
        }

        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session !== null) {
            $lastRequestAt = (int)$session->get('product_request_last_at', 0);
            if ($lastRequestAt > 0 && (time() - $lastRequestAt) < 20) {
                return $this->json(['message' => 'Слишком частые отправки. Повторите через несколько секунд.'], 429);
            }
        }

        if ($advertisementId <= 0 || $sideCode === '' || $contactName === '' || $contactPhone === '') {
            return $this->json(['message' => 'Заполните конструкцию, сторону и контактные данные.'], 422);
        }

        $advertisement = $repository->find($advertisementId);
        if (!$advertisement instanceof Advertisement) {
            return $this->json(['message' => 'Конструкция не найдена.'], 404);
        }

        if (!in_array($sideCode, $advertisement->getSides(), true)) {
            return $this->json(['message' => 'У выбранной конструкции нет указанной стороны.'], 422);
        }

        $productRequest = (new ProductRequest())
            ->setAdvertisement($advertisement)
            ->setSideCode($sideCode)
            ->setContactName($contactName)
            ->setContactPhone($contactPhone)
            ->setComment($comment)
            ->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($productRequest);
        $entityManager->flush();

        if ($session !== null) {
            $session->set('product_request_last_at', time());
        }

        return $this->json(['message' => 'Заявка отправлена.', 'id' => $productRequest->getId()], 201);
    }
}
