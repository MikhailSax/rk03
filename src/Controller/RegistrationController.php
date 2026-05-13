<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private readonly EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'limiter.registration')] RateLimiterFactory $registrationLimiter,
    ): Response {
        $limiter = $registrationLimiter->create($request->getClientIp() ?? 'unknown');
        $rateLimit = $limiter->consume();

        if (!$rateLimit->isAccepted()) {
            $this->addFlash('verify_email_error', 'Слишком много попыток регистрации. Попробуйте немного позже.');

            return $this->redirectToRoute('app_register');
        }

        $user = new User();
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setRoles(['ROLE_USER']);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $honeypot = trim((string) $form->get('website')->getData());
            $formStartedAt = (int) $form->get('formStartedAt')->getData();
            $nowMs = (int) floor(microtime(true) * 1000);

            if ($honeypot !== '') {
                $this->addFlash('verify_email_error', 'Запрос отклонён.');

                return $this->redirectToRoute('app_register');
            }

            if ($formStartedAt > 0 && $nowMs - $formStartedAt < 2500) {
                $this->addFlash('verify_email_error', 'Пожалуйста, отправьте форму чуть позже.');

                return $this->redirectToRoute('app_register');
            }

            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                $this->emailVerifier->sendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('noreply@bmsbur.ru', 'БМС'))
                        ->to((string) $user->getEmail())
                        ->subject('Подтвердите ваш email')
                        ->htmlTemplate('registration/confirmation_email.html.twig'),
                );
                $this->addFlash('success', 'Регистрация успешна. Проверьте почту для подтверждения email.');

                return $this->redirectToRoute('app_login');
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('verify_email_error', 'Пользователь с таким email или телефоном уже существует.');
            } catch (\Throwable) {
                $this->addFlash('verify_email_error', 'Произошла ошибка при регистрации. Попробуйте позже.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Email успешно подтверждён.');

        return $this->redirectToRoute('profile');
    }
}
