<?php

declare(strict_types=1);

namespace App\Domain\Users\Errors;

use App\Shared\Results\Error;
use App\Shared\Enums\HttpStatus;

class UserErrors
{
    public static function notFound(string $userUuid): Error
    {
        return new Error(
            code: 'Users.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'User not found',
            detail: "User with ID {$userUuid} does not exist",
            errorMessage: "User with ID {$userUuid} does not exist",
        );
    }

    public static function registrationFailed(): Error
    {
        return new Error(
            code: 'Users.RegistrationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'User registration failed',
            detail: 'An unexpected error occurred during user registration',
            errorMessage: 'An unexpected error occurred during user registration',
        );
    }

    public static function emailAlreadyExists(string $email): Error
    {
        return new Error(
            code: 'Users.EmailAlreadyExists',
            status: HttpStatus::CONFLICT,
            description: 'Email already registered',
            detail: "The email {$email} is already registered",
            errorMessage: "The email {$email} is already registered",
        );
    }

    public static function nameAlreadyExists(string $name): Error
    {
        return new Error(
            code: 'Users.NameAlreadyExists',
            status: HttpStatus::CONFLICT,
            description: 'Username already taken',
            detail: "The username {$name} is already taken",
            errorMessage: "The username {$name} is already taken",
        );
    }

    public static function tokenGenerationFailed(): Error
    {
        return new Error(
            code: 'Users.TokenGenerationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Token generation failed',
            detail: 'Failed to generate authentication token',
            errorMessage: 'Failed to generate authentication token',
        );
    }

    // Login
    public static function invalidCredentials(): Error
    {
        return new Error(
            code: 'Users.InvalidCredentials',
            status: HttpStatus::UNAUTHORIZED,
            description: 'Invalid credentials',
            detail: 'The provided email or password is incorrect',
            errorMessage: 'Invalid email or password',
        );
    }

    public static function loginFailed(): Error
    {
        return new Error(
            code: 'Users.LoginFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Login failed',
            detail: 'An unexpected error occurred during login',
            errorMessage: 'An unexpected error occurred during login',
        );
    }

    public static function notAuthenticated(): Error
    {
        return new Error(
            code: 'Users.NotAuthenticated',
            status: HttpStatus::UNAUTHORIZED,
            description: 'Not authenticated',
            detail: 'User is not authenticated',
            errorMessage: 'User is not authenticated',
        );
    }

    public static function notAuthorized(): Error
    {
        return new Error(
            code: 'Users.NotAuthorized',
            status: HttpStatus::UNAUTHORIZED,
            description: 'Not authorized',
            detail: 'User is not authorized',
            errorMessage: 'User is not authorized',
        );
    }

    public static function logoutFailed(): Error
    {
        return new Error(
            code: 'Users.LogoutFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Logout failed',
            detail: 'An unexpected error occurred during logout',
            errorMessage: 'An unexpected error occurred during logout',
        );
    }

    public static function roleAlreadyAssigned(string $roleName): Error
    {
        return new Error(
            code: 'Users.RoleAlreadyAssigned',
            status: HttpStatus::CONFLICT,
            description: "Role '{$roleName}' is already assigned to the user.",
            detail: "The user already has the role '{$roleName}'.",
            errorMessage: "Role '{$roleName}' is already assigned.",
        );
    }

    public static function roleNotAssigned(string $roleName): Error
    {
        return new Error(
            code: 'Users.RoleNotAssigned',
            status: HttpStatus::NOT_FOUND,
            description: "Role '{$roleName}' is not assigned to the user.",
            detail: "Role '{$roleName}' not found on user.",
            errorMessage: "Role '{$roleName}' not found on user.",
        );
    }
}
