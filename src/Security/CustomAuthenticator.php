<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class CustomAuthenticator extends AbstractAuthenticator
{
    /**
     * Cette méthode détermine si l'authenticator doit être utilisé pour la requête.
     * Ici, il s'applique lorsque l'en-tête X-AUTH-TOKEN est présent.
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    /**
     * Récupère le token et crée un Passport pour l'authentification.
     */
    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');
        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        // La fonction de callback permet de récupérer l'utilisateur associé au token.
        // Vous devez remplacer la logique ci-dessous par votre propre recherche, par exemple via votre repository.
        return new SelfValidatingPassport(
            new UserBadge($apiToken, function (string $userIdentifier) {
                // Exemple fictif : supposez que le token est en fait le nom d'utilisateur.
                // Remplacez ce bloc par la recherche d'utilisateur via votre repository.
                // $user = $this->userRepository->findOneBy(['apiToken' => $userIdentifier]);
                $user = null; // À remplacer par la logique de récupération de l'utilisateur.
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Invalid API token');
                }
                return $user;
            })
        );
    }

    /**
     * En cas de succès, on laisse la requête continuer.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * En cas d'échec, retourne une réponse JSON avec le message d'erreur.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
