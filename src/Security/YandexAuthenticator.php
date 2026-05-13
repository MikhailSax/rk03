<?php

namespace App\Security;

use Aego\OAuth2\Client\Provider\YandexResourceOwner;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class YandexAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly RouterInterface $router,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'connect_yandex_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('yandex');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var YandexResourceOwner $yandexUser */
                $yandexUser = $client->fetchUserFromToken($accessToken);
                $userData = $yandexUser->toArray();

                $yandexId = (string) $yandexUser->getId();
                $email = $yandexUser->getEmail() ?? ($userData['default_email'] ?? null);
                if ($email === null) {
                    $email = sprintf('yandex_%s@auth.local', $yandexId);
                }

                $name = $userData['first_name'] ?? 'Пользователь';
                $lastName = $userData['last_name'] ?? null;
                $rawPhone = $userData['default_phone']['number'] ?? $userData['default_phone'] ?? null;

                $phone = is_string($rawPhone) ? $rawPhone : sprintf('+7999%07d', abs(crc32($yandexId)) % 10000000);

                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['yandexId' => $yandexId]);
                if ($existingUser instanceof User) {
                    return $existingUser;
                }

                $existingByEmail = $this->entityManager->getRepository(User::class)->findOneBy(['email' => mb_strtolower((string) $email)]);
                if ($existingByEmail instanceof User) {
                    $existingByEmail->setYandexId($yandexId);
                    $this->entityManager->flush();

                    return $existingByEmail;
                }

                $user = new User();
                $user
                    ->setYandexId($yandexId)
                    ->setEmail((string) $email)
                    ->setFirstName((string) $name)
                    ->setLastName($lastName)
                    ->setPhone($phone)
                    ->setRoles(['ROLE_USER'])
                    ->setIsVerified(true)
                    ->setCreatedAt(new \DateTimeImmutable());

                $temporaryPassword = bin2hex(random_bytes(16));
                $user->setPassword($this->passwordHasher->hashPassword($user, $temporaryPassword));

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('error', 'Ошибка авторизации через Яндекс. Попробуйте снова.');
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }
}
