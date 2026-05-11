<?php

namespace App\Domain\Catalogues\Errors;

use App\Shared\Results\ResultError;
use App\Shared\Enums\HttpStatus;

class CatalogueErrors
{
    public static function notFound(string $catalogueUid): ResultError
    {
        return new ResultError(
            code: 'Catalogues.NotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Catalogue not found',
            detail: "Catalogue with ID {$catalogueUid} does not exist",
            errorMessage: "Catalogue with ID {$catalogueUid} does not exist",
        );
    }

    public static function accessDenied(string $catalogueUid): ResultError
    {
        return new ResultError(
            code: 'Catalogues.AccessDenied',
            status: HttpStatus::FORBIDDEN,
            description: 'Access denied',
            detail: "You don't have permission to access catalogue {$catalogueUid}",
            errorMessage: "You don't have permission to access catalogue {$catalogueUid}",
        );
    }

    public static function invalidItemForType(string $catalogueUid, int $itemId): ResultError
    {
        return new ResultError(
            code: 'Catalogues.InvalidItemForType',
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            description: 'Invalid catalogue item',
            detail: "Item {$itemId} cannot be added to catalogue {$catalogueUid}",
            errorMessage: "Item {$itemId} cannot be added to catalogue {$catalogueUid}",
        );
    }

    public static function duplicateItem(string $catalogueUid, int $itemId): ResultError
    {
        return new ResultError(
            code: 'Catalogues.DuplicateItem',
            status: HttpStatus::CONFLICT,
            description: 'Catalogue already contains item',
            detail: "Item {$itemId} is already present in catalogue {$catalogueUid}",
            errorMessage: "Item {$itemId} is already present in catalogue {$catalogueUid}",
        );
    }

    public static function creationFailed(): ResultError
    {
        return new ResultError(
            code: 'Catalogues.CreationFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Catalogue creation failed',
            detail: 'An unexpected error occurred during catalogue creation',
            errorMessage: 'An unexpected error occurred during catalogue creation',
        );
    }

    public static function updateFailed(string $errorMessage): ResultError
    {
        return new ResultError(
            code: 'Catalogues.UpdateFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Catalogue update failed',
            detail: 'An unexpected error occurred during catalogue updating',
            errorMessage: $errorMessage,
        );
    }

    public static function addItemFailed(): ResultError
    {
        return new ResultError(
            code: 'Catalogues.AddItemFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Catalogue item add failed',
            detail: 'An unexpected error occurred while adding an item to the catalogue',
            errorMessage: 'An unexpected error occurred while adding an item to the catalogue',
        );
    }

    public static function itemNotFound(string $catalogueUid, int $itemId): ResultError
    {
        return new ResultError(
            code: 'Catalogues.ItemNotFound',
            status: HttpStatus::NOT_FOUND,
            description: 'Catalogue item not found',
            detail: "Item {$itemId} is not present in catalogue {$catalogueUid}",
            errorMessage: "Item {$itemId} is not present in catalogue {$catalogueUid}",
        );
    }

    public static function removeItemFailed(): ResultError
    {
        return new ResultError(
            code: 'Catalogues.RemoveItemFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Catalogue item removal failed',
            detail: 'An unexpected error occurred while removing an item from the catalogue',
            errorMessage: 'An unexpected error occurred while removing an item from the catalogue',
        );
    }

    public static function deletionFailed(): ResultError
    {
        return new ResultError(
            code: 'Catalogues.DeletionFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'Catalogue deletion failed',
            detail: 'An unexpected error occurred during catalogue deletion',
            errorMessage: 'An unexpected error occurred during catalogue deletion',
        );
    }
}
