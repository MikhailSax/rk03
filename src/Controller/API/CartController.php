<?php

namespace App\Controller\API;

use App\Entity\Advertisement;
use App\Repository\AdvertisementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cart', name: 'api_cart_')]
class CartController extends AbstractController
{
    private const SESSION_KEY = 'map_cart_items';

    #[Route('', name: 'get', methods: ['GET'])]
    public function get(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $items = is_array($session->get(self::SESSION_KEY, [])) ? $session->get(self::SESSION_KEY, []) : [];

        return $this->json($this->buildPayload($items));
    }

    #[Route('/items', name: 'add_item', methods: ['POST'])]
    public function addItem(Request $request, AdvertisementRepository $repository): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['message' => 'Некорректный формат запроса.'], 400);
        }

        $advertisementId = (int)($payload['advertisementId'] ?? 0);
        $sideCode = mb_strtoupper(trim((string)($payload['side'] ?? '')));
        $from = \DateTimeImmutable::createFromFormat('Y-m-d', (string)($payload['startDate'] ?? '')) ?: null;
        $to = \DateTimeImmutable::createFromFormat('Y-m-d', (string)($payload['endDate'] ?? '')) ?: null;

        /** @var Advertisement|null $advertisement */
        $advertisement = $repository->find($advertisementId);
        if (!$advertisement instanceof Advertisement || $sideCode === '' || !$from || !$to || $to < $from) {
            return $this->json(['message' => 'Некорректные данные позиции корзины.'], 422);
        }
        if (!in_array($sideCode, $advertisement->getSides(), true)) {
            return $this->json(['message' => sprintf('Сторона %s недоступна.', $sideCode)], 422);
        }

        $side = $advertisement->getSideByCode($sideCode);
        $item = [
            'advertisementId' => $advertisement->getId(),
            'address' => $this->resolveAddress($advertisement),
            'side' => $sideCode,
            'startDate' => $from->format('Y-m-d'),
            'endDate' => $to->format('Y-m-d'),
            'price' => (float)($side?->getPrice() ?? 0),
        ];

        $session = $request->getSession();
        $items = is_array($session->get(self::SESSION_KEY, [])) ? $session->get(self::SESSION_KEY, []) : [];
        foreach ($items as $existing) {
            if (
                (int)($existing['advertisementId'] ?? 0) === $item['advertisementId']
                && (string)($existing['side'] ?? '') === $item['side']
                && (string)($existing['startDate'] ?? '') === $item['startDate']
                && (string)($existing['endDate'] ?? '') === $item['endDate']
            ) {
                return $this->json(['message' => 'Позиция уже находится в корзине.'] + $this->buildPayload($items), 200);
            }
        }

        $items[] = $item;
        $session->set(self::SESSION_KEY, $items);

        return $this->json(['message' => 'Позиция добавлена в корзину.'] + $this->buildPayload($items), 201);
    }

    #[Route('/items/{index}', name: 'remove_item', requirements: ['index' => '\d+'], methods: ['DELETE'])]
    public function removeItem(Request $request, int $index): JsonResponse
    {
        $session = $request->getSession();
        $items = array_values(is_array($session->get(self::SESSION_KEY, [])) ? $session->get(self::SESSION_KEY, []) : []);

        if (!array_key_exists($index, $items)) {
            return $this->json(['message' => 'Позиция не найдена.'], 404);
        }

        array_splice($items, $index, 1);
        $session->set(self::SESSION_KEY, $items);

        return $this->json(['message' => 'Позиция удалена.'] + $this->buildPayload($items));
    }

    #[Route('', name: 'clear', methods: ['DELETE'])]
    public function clear(Request $request): JsonResponse
    {
        $request->getSession()->set(self::SESSION_KEY, []);

        return $this->json(['message' => 'Корзина очищена.'] + $this->buildPayload([]));
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function buildPayload(array $items): array
    {
        $normalized = array_values($items);
        $total = array_reduce($normalized, static function (float $sum, array $item): float {
            return $sum + (float)($item['price'] ?? 0);
        }, 0.0);

        return [
            'items' => $normalized,
            'count' => count($normalized),
            'total' => $total,
        ];
    }

    private function resolveAddress(Advertisement $advertisement): string
    {
        if (method_exists($advertisement, 'getAddress')) {
            $address = (string)$advertisement->getAddress();
            if ($address !== '') {
                return $address;
            }
        }

        $code = method_exists($advertisement, 'getCode') ? (string)$advertisement->getCode() : '';
        if ($code !== '') {
            return $code;
        }

        $placeNumber = method_exists($advertisement, 'getPlaceNumber') ? (string)$advertisement->getPlaceNumber() : '';
        if ($placeNumber !== '') {
            return $placeNumber;
        }

        return sprintf('#%d', (int)$advertisement->getId());
    }
}

