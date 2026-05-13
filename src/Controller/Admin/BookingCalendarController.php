<?php

namespace App\Controller\Admin;

use App\Repository\AdvertisementBookingRepository;
use App\Repository\AdvertisementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookingCalendarController extends AbstractController
{
    #[Route('/admin/booking-calendar', name: 'admin_booking_calendar')]
    public function index(
        Request $request,
        AdvertisementRepository $advertisementRepository,
        AdvertisementBookingRepository $bookingRepository,
    ): Response {
        $advertisements = $advertisementRepository->findBy([], ['id' => 'ASC']);
        $adId = (int) $request->query->get('ad', 0);
        $selectedSide = mb_strtoupper(trim((string) $request->query->get('side', '')));
        $monthParam = (string) $request->query->get('month', (new \DateTimeImmutable('first day of this month'))->format('Y-m'));

        $monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%s-01', $monthParam));
        if (!$monthStart instanceof \DateTimeImmutable) {
            $monthStart = new \DateTimeImmutable('first day of this month');
        }

        $monthStart = $monthStart->setTime(0, 0);
        $monthEnd = $monthStart->modify('last day of this month');

        $selectedAdvertisement = null;
        foreach ($advertisements as $advertisement) {
            if ($advertisement->getId() === $adId) {
                $selectedAdvertisement = $advertisement;
                break;
            }
        }

        $availableSides = [];
        if ($selectedAdvertisement !== null) {
            $availableSides = $selectedAdvertisement->getSides();
            if ($selectedSide !== '' && !in_array($selectedSide, $availableSides, true)) {
                $selectedSide = '';
            }
        }

        $bookings = [];
        $days = [];

        if ($selectedAdvertisement !== null) {
            $bookings = $bookingRepository->findByAdvertisementAndMonth($selectedAdvertisement, $monthStart, $monthEnd, $selectedSide !== '' ? $selectedSide : null);

            $cursor = $monthStart;
            while ($cursor <= $monthEnd) {
                $busy = false;
                foreach ($bookings as $booking) {
                    if ($booking->getStartDate() <= $cursor && $booking->getEndDate() >= $cursor) {
                        $busy = true;
                        break;
                    }
                }

                $days[] = [
                    'date' => $cursor,
                    'busy' => $busy,
                ];

                $cursor = $cursor->modify('+1 day');
            }
        }

        return $this->render('admin/booking_calendar.html.twig', [
            'advertisements' => $advertisements,
            'selectedAdvertisement' => $selectedAdvertisement,
            'selectedSide' => $selectedSide,
            'availableSides' => $availableSides,
            'month' => $monthStart,
            'days' => $days,
            'bookings' => $bookings,
        ]);
    }
}
