<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use App\Repository\OrderRepository;
use App\Repository\ProductRequestRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = (string) $form->get('currentPassword')->getData();
            $newPassword = (string) $form->get('newPassword')->getData();

            if (($currentPassword === '') !== ($newPassword === '')) {
                $this->addFlash('error', 'Чтобы сменить пароль, заполните оба поля: текущий и новый пароль.');
            } elseif ($currentPassword !== '') {
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Текущий пароль указан неверно.');

                    return $this->redirectToRoute('profile');
                }

                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            $user->setUpdatedAt(new \DateTimeImmutable());

            try {
                $entityManager->flush();
                $this->addFlash('success', 'Профиль успешно обновлён.');

                return $this->redirectToRoute('profile');
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Пользователь с таким email или телефоном уже существует.');
            } catch (\Throwable) {
                $this->addFlash('error', 'Не удалось сохранить изменения. Попробуйте позже.');
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route('/profile/orders', name: 'profile.orders')]
    public function orders(
        ProductRequestRepository $productRequestRepository,
        OrderRepository $orderRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $userOrders = $orderRepository->findByUserOrdered($user);

        $userRequests = [];
        if ($user->getPhone()) {
            $userRequests = $productRequestRepository->findLatestByContactPhone($user->getPhone());
        }

        return $this->render('profile/orders.html.twig', [
            'user' => $user,
            'userOrders' => $userOrders,
            'userRequests' => $userRequests,
        ]);
    }
}
