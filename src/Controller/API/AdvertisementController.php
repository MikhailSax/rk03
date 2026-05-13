<?php

namespace App\Controller\API;

use App\Entity\Advertisement;
use App\Repository\AdvertisementRepository;
use App\Repository\AdvertisementTypeRepository;
use App\Repository\AdvertisementCategoryRepository;
use App\Services\AdvertisementService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ProductRequest;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api', name: 'api_')]
class AdvertisementController extends AbstractController
{
    public function __construct(
        protected AdvertisementService $advertisementService,
    )
    {

    }

    #[Route('/advertisements', name: 'advertisements_list', methods: ['GET'])]
    public function list(Request $request, AdvertisementRepository $repository): JsonResponse
    {
        $category = $request->query->get('productType');
        $type = $request->query->get('constrTypeId');

        if (!empty($category) || !empty($type)) {
            $ads = $repository->findByFiltersForApi((int) $category, (int) $type);

            return $this->json($this->advertisementService->getData($ads));
        }

        $ads = $repository->findForApi();

        return $this->json($this->advertisementService->getData($ads));
    }

    #[Route('/advertisements/{id}', name: 'advertisements_show', methods: ['GET'])]
    public function show(Advertisement $advertisement): JsonResponse
    {
        $data = $this->advertisementService->getData([$advertisement]);

        return $this->json($data[0] ?? []);
    }


    #[Route('/product-requests', name: 'product_requests_create', methods: ['POST'])]
    public function createProductRequest(Request $request, AdvertisementRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['message' => 'Некорректный формат запроса.'], 400);
        }

        $advertisementId = (int) ($payload['advertisementId'] ?? 0);
        $sideCode = mb_strtoupper(trim((string) ($payload['side'] ?? '')));
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

        $honeypot = trim((string) ($payload['website'] ?? ''));
        $formStartedAt = (int) ($payload['formStartedAt'] ?? 0);

        if ($honeypot !== '') {
            return $this->json(['message' => 'Запрос отклонён.'], 422);
        }

        $nowMs = (int) floor(microtime(true) * 1000);
        if ($formStartedAt > 0 && $nowMs - $formStartedAt < 2500) {
            return $this->json(['message' => 'Пожалуйста, отправьте форму чуть позже.'], 429);
        }

        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session !== null) {
            $lastRequestAt = (int) $session->get('product_request_last_at', 0);
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

        if (isset($session) && $session !== null) {
            $session->set('product_request_last_at', time());
        }

        return $this->json(['message' => 'Заявка отправлена.', 'id' => $productRequest->getId()], 201);
    }

    #[Route('/filters', name: 'filters', methods: ['GET'])]
    public function filters(
        AdvertisementTypeRepository     $typeRepo,
        AdvertisementCategoryRepository $catRepo,
        Request                         $request,
    ): JsonResponse
    {
        $category_id = $request->query->get('productType');

        if (!empty($category_id)) {
            $constrTypes = $typeRepo->findFilter($category_id);
            return $this->json([
                'productTypes' => array_map(fn($c) => [
                    'id' => $c->getId(),
                    'name' => $c->getName()
                ], $catRepo->findAll()),
                'constrTypes' => array_map(fn($c) => [
                    'id' => $c['id'],
                    'name' => $c['name']

                ], $constrTypes)
            ]);
        }

        return $this->json([
            'productTypes' => array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName()
            ], $catRepo->findAll()),

            'constrTypes' => array_map(fn($t) => [
                'id' => $t->getId(),
                'name' => $t->getName()
            ], $typeRepo->findAll()),

            // если районы хардкодим – вернём статикой
        ]);
    }

    private function serializeAdvertisement(Advertisement $ad): array
    {
        return [
            'id' => $ad->getId(),
            'place_number' => $ad->getPlaceNumber(),
            'code' => $ad->getCode(),
            'address' => $ad->getAddress(),
            'sides' => $ad->getSides(),

            'type' => $ad->getType() ? [
                'id' => $ad->getType()->getId(),
                'name' => $ad->getType()->getName(),
                'category' => $ad->getType()->getCategory()?->getName()
            ] : null,

            'location' => $ad->getLocation() ? [
                'latitude' => $ad->getLocation()->getLatitude(),
                'longitude' => $ad->getLocation()->getLongitude(),
                'azimuth' => $ad->getLocation()->getAzimuth()
            ] : null,

            'bookings' => array_map(static fn($booking) => [
                'id' => $booking->getId(),
                'clientName' => $booking->getClientName(),
                'startDate' => $booking->getStartDate()?->format('Y-m-d'),
                'endDate' => $booking->getEndDate()?->format('Y-m-d'),
            ], $ad->getBookings()->toArray()),

            // 🔥 временно генерируем цену (или добавь в БД)
            'price' => random_int(15000, 50000),

            // район можно вычислять/присвоить вручную
            'areaId' => random_int(1, 5)
        ];
    }
}
