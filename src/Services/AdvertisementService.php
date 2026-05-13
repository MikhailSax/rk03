<?php

namespace App\Services;

use App\Entity\Advertisement;
use App\Entity\AdvertisementBooking;
use App\Entity\AdvertisementSide;

class AdvertisementService
{
    /**
     * @param array<int, Advertisement|array<string, mixed>> $ads
     */
    public function getData(array $ads): array
    {
        return array_map(function (Advertisement|array $item): array {
            if (is_array($item)) {
                return $this->serializeLegacyRow($item);
            }

            $sideDetails = $this->serializeSides($item);

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
                'bookings' => array_map(static fn (AdvertisementBooking $booking): array => [
                    'id' => $booking->getId(),
                    'side_code' => $booking->getSideCode(),
                    'client_name' => $booking->getClientName(),
                    'start_date' => $booking->getStartDate()?->format('Y-m-d'),
                    'end_date' => $booking->getEndDate()?->format('Y-m-d'),
                ], $item->getBookings()->toArray()),
            ];
        }, $ads);
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
            'side_details' => array_map(static fn (string $code): array => [
                'code' => $code,
                'description' => null,
                'price' => null,
                'image' => null,
                'image_url' => null,
                'night_image' => null,
                'night_image_url' => null,
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

    /**
     * @return string[]
     */
    private function normalizeSides(mixed $sides): array
    {
        if (is_array($sides)) {
            return array_values(array_filter(array_map(static fn (mixed $side): string => mb_strtoupper(trim((string) $side)), $sides)));
        }

        if (is_string($sides) && $sides !== '') {
            $decoded = json_decode($sides, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map(static fn (mixed $side): string => mb_strtoupper(trim((string) $side)), $decoded)));
            }

            return array_values(array_filter(array_map(static fn (string $side): string => mb_strtoupper(trim($side)), explode(',', $sides))));
        }

        return [];
    }

    /**
     * @return array<int, array{code: string, description: ?string, price: ?string, image: ?string, image_url: ?string}>
     */
    private function serializeSides(Advertisement $advertisement): array
    {
        $sideItems = $advertisement->getSideItems()->toArray();

        if ($sideItems === []) {
            $codes = $advertisement->getSides();

            if ($codes === []) {
                if ($advertisement->getSideADescription() !== null || $advertisement->getSideAPrice() !== null || $advertisement->getSideAImage() !== null) {
                    $codes[] = 'A';
                }
                if ($advertisement->getSideBDescription() !== null || $advertisement->getSideBPrice() !== null || $advertisement->getSideBImage() !== null) {
                    $codes[] = 'B';
                }
            }

            return array_map(function (string $code) use ($advertisement): array {
                $isA = $code === 'A';

                return [
                    'code' => $code,
                    'description' => $isA ? $advertisement->getSideADescription() : ($code === 'B' ? $advertisement->getSideBDescription() : null),
                    'price' => $isA ? $advertisement->getSideAPrice() : ($code === 'B' ? $advertisement->getSideBPrice() : null),
                    'image' => $isA ? $advertisement->getSideAImage() : ($code === 'B' ? $advertisement->getSideBImage() : null),
                    'image_url' => $this->buildImageUrl($isA ? $advertisement->getSideAImage() : ($code === 'B' ? $advertisement->getSideBImage() : null)),
                    'night_image' => null,
                    'night_image_url' => null,
                ];
            }, $codes);
        }

        usort($sideItems, static fn (AdvertisementSide $left, AdvertisementSide $right): int => strcmp((string) $left->getCode(), (string) $right->getCode()));

        return array_map(function (AdvertisementSide $side) use ($advertisement): array {
            $code = (string) $side->getCode();
            $isA = $code === 'A';

            $description = $side->getDescription() ?? ($isA ? $advertisement->getSideADescription() : ($code === 'B' ? $advertisement->getSideBDescription() : null));
            $price = $side->getPrice() ?? ($isA ? $advertisement->getSideAPrice() : ($code === 'B' ? $advertisement->getSideBPrice() : null));
            $image = $side->getImage() ?? ($isA ? $advertisement->getSideAImage() : ($code === 'B' ? $advertisement->getSideBImage() : null));

            return [
                'code' => $code,
                'description' => $description,
                'price' => $price,
                'image' => $image,
                'image_url' => $this->buildImageUrl($image),
                'night_image' => $side->getNightImage(),
                'night_image_url' => $this->buildImageUrl($side->getNightImage()),
            ];
        }, $sideItems);
    }

    private function buildImageUrl(?string $image): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }

        if (preg_match('~^https?://cloud\\.mail\\.ru/public/([^/]+)/([^/?#]+)~i', $image, $matches) === 1) {
            return sprintf('https://thumb.cloud.mail.ru/weblink/thumb/xw0/%s/%s?wm=true', $matches[1], $matches[2]);
        }

        if (preg_match('#^https?://#i', $image) === 1) {
            return $image;
        }

        return '/uploads/advertisements/' . ltrim($image, '/');
    }
}
