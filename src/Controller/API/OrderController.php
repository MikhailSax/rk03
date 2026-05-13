<?php

namespace App\Controller\API;

use App\Entity\Advertisement;
use App\Entity\AdvertisementBooking;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\AdvertisementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/orders', name: 'api_orders_')]
class OrderController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST', 'GET'])]
    public function create(Request $request, AdvertisementRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || $payload === []) {
            $payload = $request->query->all();
        }
        if (!is_array($payload) || $payload === []) {
            return $this->json(['message' => 'Некорректный формат запроса.'], 400);
        }

        $honeypot = trim((string) ($payload['website'] ?? ''));
        $formStartedAt = (int) ($payload['formStartedAt'] ?? 0);
        if ($honeypot !== '') {
            return $this->json(['message' => 'Запрос отклонён.'], 422);
        }
        $nowMs = (int) floor(microtime(true) * 1000);
        if ($formStartedAt > 0 && $nowMs - $formStartedAt < 800) {
            return $this->json(['message' => 'Пожалуйста, отправьте форму чуть позже.'], 429);
        }

        $session = $request->hasSession() ? $request->getSession() : null;
        $isAuthenticated = $this->getUser() instanceof User;
        if ($session !== null) {
            $lastRequestAtMs = (int) $session->get('order_submit_last_at_ms', 0);
            if (!$isAuthenticated && $lastRequestAtMs > 0 && ($nowMs - $lastRequestAtMs) < 3000) {
                return $this->json(['message' => 'Слишком частые отправки. Повторите через несколько секунд.'], 429);
            }
        }

        $contactName = trim((string) ($payload['contactName'] ?? ''));
        $contactPhone = trim((string) ($payload['contactPhone'] ?? ''));
        $comment = isset($payload['comment']) ? trim((string) $payload['comment']) : null;

        $user = $this->getUser();
        if ($user instanceof User) {
            $fullName = trim(sprintf('%s %s', $user->getFirstName() ?? '', $user->getLastName() ?? ''));
            if ($fullName !== '') {
                $contactName = $fullName;
            }
            if (($user->getPhone() ?? '') !== '') {
                $contactPhone = (string) $user->getPhone();
            }
        }

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        if ($contactName === '' || $contactPhone === '' || $items === []) {
            return $this->json(['message' => 'Заполните контакты и добавьте конструкции в корзину.'], 422);
        }

        $now = new \DateTimeImmutable();
        $order = (new Order())
            ->setContactName($contactName)
            ->setContactPhone($contactPhone)
            ->setComment($comment)
            ->setStatus(Order::STATUS_PENDING)
            ->setCreatedAt($now)
            ->setReservedUntil($now->modify('+24 hours'));

        if ($user instanceof User) {
            $order->setUser($user);
        }

        foreach ($items as $item) {
            $advertisementId = (int) ($item['advertisementId'] ?? 0);
            $sideCode = mb_strtoupper(trim((string) ($item['side'] ?? '')));
            $from = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($item['startDate'] ?? '')) ?: null;
            $to = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($item['endDate'] ?? '')) ?: null;

            $advertisement = $repository->find($advertisementId);
            if (!$advertisement instanceof Advertisement || $sideCode === '' || !$from || !$to || $to < $from) {
                return $this->json(['message' => 'В корзине есть некорректные позиции.'], 422);
            }
            if (!in_array($sideCode, $advertisement->getSides(), true)) {
                return $this->json(['message' => sprintf('Сторона %s недоступна.', $sideCode)], 422);
            }

            foreach ($advertisement->getBookings() as $booking) {
                if ($booking->getSideCode() !== $sideCode) {
                    continue;
                }
                $start = $booking->getStartDate();
                $end = $booking->getEndDate();
                if ($start && $end && $start <= $to && $end >= $from) {
                    return $this->json(['message' => sprintf('Сторона %s уже занята в выбранный период.', $sideCode)], 409);
                }
            }

            $side = $advertisement->getSideByCode($sideCode);
            $orderItem = (new OrderItem())
                ->setAdvertisement($advertisement)
                ->setSideCode($sideCode)
                ->setStartDate($from)
                ->setEndDate($to)
                ->setPriceSnapshot($side?->getPrice());
            $order->addItem($orderItem);

            $booking = (new AdvertisementBooking())
                ->setAdvertisement($advertisement)
                ->setSideCode($sideCode)
                ->setClientName($contactName)
                ->setStartDate($from)
                ->setEndDate($to)
                ->setComment('Бронь 24 часа до оплаты')
                ->setOrderRef($order);

            $entityManager->persist($booking);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        if ($session !== null) {
            $session->set('order_submit_last_at_ms', $nowMs);
        }

        return $this->json([
            'message' => 'Заказ создан. Бронь действует 24 часа до оплаты.',
            'orderId' => $order->getId(),
            'reservedUntil' => $order->getReservedUntil()?->format(DATE_ATOM),
        ], 201);
    }
}