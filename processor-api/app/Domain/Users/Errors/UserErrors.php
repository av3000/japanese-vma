<?php

declare(strict_types=1);

namespace App\Domain\Users\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class UserErrors
{
    public static function notFound(string $userUuid): ResultError
    {
        return new ResultError(
            code: 'Users.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'User not found',
            detail: "User with ID {$userUuid} does not exist",
            errorMessage: "User with ID {$userUuid} does not exist",
        );
    }

    public static function registrationFailed(): ResultError
    {
        return new ResultError(
            code: 'Users.RegistrationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'User registration failed',
            detail: 'An unexpected error occurred during user registration',
            errorMessage: 'An unexpected error occurred during user registration',
        );
    }

    public static function emailAlreadyExists(string $email): ResultError
    {
        return new ResultError(
            code: 'Users.EmailAlreadyExists',
            status: HttpStatus::CONFLICT,
            description: 'Email already registered',
            detail: "The email {$email} is already registered",
            errorMessage: "The email {$email} is already registered",
        );
    }

    public static function nameAlreadyExists(string $name): ResultError
    {
        return new ResultError(
            code: 'Users.NameAlreadyExists',
            status: HttpStatus::CONFLICT,
            description: 'Username already taken',
            detail: "The username {$name} is already taken",
            errorMessage: "The username {$name} is already taken",
        );
    }

    public static function tokenGenerationFailed(): ResultError
    {
        return new ResultError(
            code: 'Users.TokenGenerationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Token generation failed',
            detail: 'Failed to generate authentication token',
            errorMessage: 'Failed to generate authentication token',
        );
    }

    // Login
    public static function invalidCredentials(): ResultError
    {
        return new ResultError(
            code: 'Users.InvalidCredentials',
            status: HttpStatus::UNAUTHORIZED,
            description: 'Invalid credentials',
            detail: 'The provided email or password is incorrect',
            errorMessage: 'Invalid email or password',
        );
    }

    public static function loginFailed(): ResultError
    {
        return new ResultError(
            code: 'Users.LoginFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Login failed',
            detail: 'An unexpected error occurred during login',
            errorMessage: 'An unexpected error occurred during login',
        );
    }

    public static function notAuthenticated(): ResultError
    {
        return new ResultError(
            code: 'Users.NotAuthenticated',
            status: HttpStatus::UNAUTHORIZED,
            description: 'Not authenticated',
            detail: 'User is not authenticated',
            errorMessage: 'User is not authenticated',
        );
    }

    public static function notAuthorized(): ResultError
    {
        return new ResultError(
            code: 'Users.NotAuthorized',
            status: HttpStatus::UNAUTHORIZED,
            description: 'Not authorized',
            detail: 'User is not authorized',
            errorMessage: 'User is not authorized',
        );
    }

    public static function logoutFailed(): ResultError
    {
        return new ResultError(
            code: 'Users.LogoutFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Logout failed',
            detail: 'An unexpected error occurred during logout',
            errorMessage: 'An unexpected error occurred during logout',
        );
    }

    public static function adminAccessDenied(): ResultError
    {
        return new ResultError(
            code: 'Users.AdminAccessDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to administer user accounts",
            errorMessage: "You don't have permission to administer user accounts.",
        );
    }

    public static function accountUpdateFailed(): ResultError
    {
        return new ResultError(
            code: 'Users.AccountUpdateFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Account update failed',
            detail: 'An unexpected error occurred while changing the account',
            errorMessage: 'The account could not be changed. Nothing was saved.',
        );
    }

    public static function roleAlreadyAssigned(string $roleName): ResultError
    {
        return new ResultError(
            code: 'Users.RoleAlreadyAssigned',
            status: HttpStatus::CONFLICT,
            description: "Role '{$roleName}' is already assigned to the user.",
            detail: "The user already has the role '{$roleName}'.",
            errorMessage: "Role '{$roleName}' is already assigned.",
        );
    }

    public static function roleNotAssigned(string $roleName): ResultError
    {
        return new ResultError(
            code: 'Users.RoleNotAssigned',
            status: HttpStatus::NOT_FOUND,
            description: "Role '{$roleName}' is not assigned to the user.",
            detail: "Role '{$roleName}' not found on user.",
            errorMessage: "Role '{$roleName}' not found on user.",
        );
    }
}
