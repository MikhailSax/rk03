<?php

namespace App\Command;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:orders:release-expired', description: 'Снимает бронь с неоплаченных заказов старше 24 часов')]
class ReleaseExpiredReservationsCommand extends Command
{
    public function __construct(private readonly OrderRepository $orderRepository, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $expired = $this->orderRepository->findExpiredPendingOrders($now);

        $releasedCount = 0;
        foreach ($expired as $order) {
            if ($order->getStatus() !== Order::STATUS_PENDING) {
                continue;
            }
            foreach ($order->getBookings() as $booking) {
                $this->entityManager->remove($booking);
                ++$releasedCount;
            }

            $order->setStatus(Order::STATUS_CANCELLED)->setExpiredAt($now);
        }

        $this->entityManager->flush();
        $io->success(sprintf('Обработано заказов: %d, снято броней: %d', count($expired), $releasedCount));

        return Command::SUCCESS;
    }
}
