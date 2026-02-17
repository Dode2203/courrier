<?php

namespace App\Service\utils;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\Clock\SystemClock;
use Symfony\Component\HttpFoundation\Request;

class JwtTokenManager
{
    private Configuration $config;

    public function __construct(string $secretKey)
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey)
        );
    }

    /**
     * Crée un token JWT signé avec des claims et une expiration
     */
    public function createToken(array $claims, int $expirationInSeconds): Plain
    {
        $now = new \DateTimeImmutable();
        $expiration = $now->modify("+$expirationInSeconds seconds");

        $builder = $this->config->builder()
            ->issuedBy('your-app')      // Issuer
            ->permittedFor('your-client') // Audience
            ->issuedAt($now)
            ->expiresAt($expiration);

        foreach ($claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return $builder->getToken($this->config->signer(), $this->config->signingKey());
    }

    /**
     * Valide un token JWT
     */
    public function validateToken(Plain $token): bool
    {
        $clock = new SystemClock(new \DateTimeZone('UTC'));

        $constraints = [
            new SignedWith($this->config->signer(), $this->config->verificationKey()),
            new ValidAt($clock) // vérifie nbf, iat, exp automatiquement
        ];

        try {
            return $this->config->validator()->validate($token, ...$constraints);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Parse un token depuis une string
     */
    public function parseToken(string $tokenString): ?Plain
    {
        try {
            $token = $this->config->parser()->parse($tokenString);
            if (!$token instanceof Plain) {
                return null;
            }
            return $token;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Récupère le token depuis le header Authorization
     */
    public function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Retourne les claims si le token est valide
     */
    public function extractClaimsFromToken(string $tokenString): ?array
    {
        $token = $this->parseToken($tokenString);
        if ($token === null) {
            return null;
        }

        if (!$this->validateToken($token)) {
            return null;
        }

        return $token->claims()->all();
    }
}
