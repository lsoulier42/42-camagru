<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testValidatePasswordAcceptsStrongPassword(): void
    {
        self::assertSame([], AuthService::validatePassword('Passw0rd!', 'Passw0rd!'));
    }

    public function testValidatePasswordRejectsWeakPasswords(): void
    {
        $errors = AuthService::validatePassword('short', 'short'); // < 8, pas de majuscule ni chiffre

        self::assertContains('Le mot de passe doit contenir au moins 8 caractères.', $errors);
        self::assertContains('Le mot de passe doit contenir au moins une majuscule.', $errors);
        self::assertContains('Le mot de passe doit contenir au moins un chiffre.', $errors);
    }

    public function testValidatePasswordRequiresLowercase(): void
    {
        $errors = AuthService::validatePassword('ABCDEFGH1', 'ABCDEFGH1'); // pas de minuscule

        self::assertContains('Le mot de passe doit contenir au moins une minuscule.', $errors);
    }

    public function testValidatePasswordChecksConfirmation(): void
    {
        $errors = AuthService::validatePassword('Passw0rd!', 'Passw0rd2!');

        self::assertContains('La confirmation du mot de passe ne correspond pas.', $errors);
    }
}
