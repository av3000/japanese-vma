<?php
namespace App\Application\Articles\Interfaces\Repositories;

interface KanjiRepositoryInterface
{
    public function findIdsByCharacters(array $characters): array;
    public function findByIds(array $ids): array;
    public function findByCharacters(array $characters): array;
}
