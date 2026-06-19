<?php

namespace App\Services;

use App\Entity\Advertisement;
use App\Entity\AdvertisementBooking;
use App\Entity\AdvertisementSide;
use App\Entity\Occupancy;
use App\Repository\OccupancyRepository;

class AdvertisementService
{
    public function __construct(
        private readonly OccupancyRepository $occupancyRepo,
    )
    {
    }

    /**
     * @param array<int, Advertisement|array<string, mixed>> $ads
     */
    public function getData(array $ads): array
    {
        $now = new \DateTimeImmutable();

        return array_map(function (Advertisement|array $item) use ($now): array {
            if (is_array($item)) {
                return $this->serializeLegacyRow($item);
            }

            $sideDetails = $this->serializeSides($item, $now);

            return [
                'id' => $item->getId(),
                'category' => $item->getType()?->getCategory()?->getName(),
                'category_id' => $item->getType()?->getCategory()?->getId(),
                'type' => $item->getType()?->getName(),
                'type_id' => $item->getType()?->getId(),
                'place_number' => $item->getPlaceNumber(),
                'sides' => array_column($sideDetails, 'code'),
                'side_details' => $sideDetails,
                'code' => $item->getCode(),
                'address' => $item->getAddress(),
                'location' => [
                    'latitude' => $item->getLocation()?->getLatitude(),
                    'longitude' => $item->getLocation()?->getLongitude(),
                ],
                'bookings' => array_map(
                    static fn(AdvertisementBooking $b): array => [
                        'id' => $b->getId(),
                        'side_code' => $b->getSideCode(),
                        'client_name' => $b->getClientName(),
                        'start_date' => $b->getStartDate()?->format('Y-m-d'),
                        'end_date' => $b->getEndDate()?->format('Y-m-d'),
                    ],
                    $item->getBookings()->toArray()
                ),
            ];
        }, $ads);
    }

    // ------------------------------------------------------------------ //

    private function serializeSides(Advertisement $advertisement, \DateTimeImmutable $now): array
    {
        $sideItems = $advertisement->getSideItems()->toArray();
        $bookings = $advertisement->getBookings()->toArray();

        if ($sideItems === []) {
            return $this->fallbackSides($advertisement);
        }

        usort($sideItems, static fn(AdvertisementSide $a, AdvertisementSide $b): int => strcmp((string)$a->getCode(), (string)$b->getCode()));

        return array_map(function (AdvertisementSide $side) use ($advertisement, $bookings, $now): array {
            $code = (string)$side->getCode();
            $isA = $code === 'A';

            $description = $side->getDescription()
                ?? ($isA ? $advertisement->getSideADescription() : ($code === 'B' ? $advertisement->getSideBDescription() : null));
            $price = $side->getPrice()
                ?? ($isA ? $advertisement->getSideAPrice() : ($code === 'B' ? $advertisement->getSideBPrice() : null));
            $image = $side->getImage()
                ?? ($isA ? $advertisement->getSideAImage() : ($code === 'B' ? $advertisement->getSideBImage() : null));

            $occupancy = $this->resolveOccupancy($side, $bookings, $now);

            return [
                'code' => $code,
                'description' => $description,
                'price' => $price,
                'image' => $image,
                'image_url' => $this->buildImageUrl($image),
                'night_image' => $side->getNightImage(),
                'night_image_url' => $this->buildImageUrl($side->getNightImage()),
                // Статус занятости: 'free' | 'busy' | 'reserved'
                'occupancy_status' => $occupancy['status'],
                'occupancy_label' => $occupancy['label'],
                'occupancy_source' => $occupancy['source'], // 'site' | '1c' | null
            ];
        }, $sideItems);
    }

    /**
     * Приоритет: бронирования с сайта → данные 1С → свободно.
     */
    private function resolveOccupancy(
        AdvertisementSide  $side,
        array              $bookings,
        \DateTimeImmutable $now,
    ): array
    {
        $code = $side->getCode();
        $monthStart = new \DateTimeImmutable($now->format('Y-m-01'));
        $monthEnd = new \DateTimeImmutable($now->format('Y-m-t'));

        // 1. Бронирования с сайта (AdvertisementBooking)
        foreach ($bookings as $booking) {
            if ($booking->getSideCode() !== $code) {
                continue;
            }
            $start = $booking->getStartDate();
            $end = $booking->getEndDate();
            if ($start !== null && $end !== null && $start <= $monthEnd && $end >= $monthStart) {
                return ['status' => 'busy', 'label' => 'Занято', 'source' => 'site'];
            }
        }

        // 2. Данные из 1С (Occupancy)
        $occ = $this->occupancyRepo->findStatusForMonth($side, $now);
        if ($occ !== null) {
            return match ($occ->getStatus()) {
                Occupancy::STATUS_FREE => ['status' => 'free', 'label' => 'Свободно', 'source' => '1c'],
                Occupancy::STATUS_BUSY => ['status' => 'busy', 'label' => 'Занято', 'source' => '1c'],
                Occupancy::STATUS_RESERVED => ['status' => 'reserved', 'label' => 'Бронь', 'source' => '1c'],
                default => ['status' => 'free', 'label' => 'Свободно', 'source' => '1c'],
            };
        }

        // 3. Нет данных
        return ['status' => 'free', 'label' => 'Свободно', 'source' => null];
    }

    // ------------------------------------------------------------------ //

    private function fallbackSides(Advertisement $advertisement): array
    {
        $codes = $advertisement->getSides();
        if ($codes === []) {
            if ($advertisement->getSideAImage() || $advertisement->getSideAPrice()) {
                $codes[] = 'A';
            }
            if ($advertisement->getSideBImage() || $advertisement->getSideBPrice()) {
                $codes[] = 'B';
            }
        }

        return array_map(static fn(string $code): array => [
            'code' => $code,
            'description' => null,
            'price' => null,
            'image' => null,
            'image_url' => null,
            'night_image' => null,
            'night_image_url' => null,
            'occupancy_status' => 'free',
            'occupancy_label' => 'Свободно',
            'occupancy_source' => null,
        ], $codes);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function serializeLegacyRow(array $row): array
    {
        $sides = $this->normalizeSides($row['side'] ?? $row['sides'] ?? []);

        return [
            'id' => $row['id'] ?? null,
            'category' => $row['category'] ?? null,
            'category_id' => $row['category_id'] ?? null,
            'type' => $row['type'] ?? null,
            'type_id' => $row['type_id'] ?? null,
            'place_number' => $row['place_number'] ?? null,
            'sides' => $sides,
            'side_details' => array_map(static fn(string $code): array => [
                'code' => $code,
                'description' => null,
                'price' => null,
                'image' => null,
                'image_url' => null,
                'night_image' => null,
                'night_image_url' => null,
                'occupancy_status' => 'free',
                'occupancy_label' => 'Свободно',
                'occupancy_source' => null,
            ], $sides),
            'code' => $row['code'] ?? null,
            'address' => $row['address'] ?? null,
            'location' => [
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
            ],
            'bookings' => [],
        ];
    }

    private function normalizeSides(mixed $sides): array
    {
        if (is_array($sides)) {
            return array_values(array_filter(
                array_map(static fn(mixed $s): string => mb_strtoupper(trim((string)$s)), $sides)
            ));
        }

        if (is_string($sides) && $sides !== '') {
            $decoded = json_decode($sides, true);
            if (is_array($decoded)) {
                return array_values(array_filter(
                    array_map(static fn($s): string => mb_strtoupper(trim((string)$s)), $decoded)
                ));
            }
            return array_values(array_filter(
                array_map(static fn(string $s): string => mb_strtoupper(trim($s)), explode(',', $sides))
            ));
        }

        return [];
    }

    private function buildImageUrl(?string $image): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }

        if (preg_match('~^https?://cloud\.mail\.ru/public/([^/]+)/([^/?#]+)~i', $image, $m) === 1) {
            return sprintf('https://thumb.cloud.mail.ru/weblink/thumb/xw0/%s/%s?wm=true', $m[1], $m[2]);
        }

        if (preg_match('#^https?://#i', $image) === 1) {
            return $image;
        }

        return '/uploads/advertisements/' . ltrim($image, '/');
    }
}
