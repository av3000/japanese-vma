<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Application\ContentImport\Interfaces\ContentSourceAdapterRegistryInterface;
use App\Application\ContentImport\Interfaces\Providers\SystemAuthorProviderInterface;
use App\Application\ContentImport\Interfaces\Readers\ImportedArticleReaderInterface;
use App\Application\ContentImport\Interfaces\Repositories\ContentSourceRepositoryInterface;
use App\Application\ContentImport\Interfaces\Repositories\ImportRunRepositoryInterface;
use App\Application\ContentImport\Services\ContentImportService;
use App\Application\ContentImport\Services\ContentImportServiceInterface;
use App\Infrastructure\ContentImport\ConfigContentSourceAdapterRegistry;
use App\Infrastructure\ContentImport\DatabaseSystemAuthorProvider;
use App\Infrastructure\ContentImport\Tagging\NullArticleTagger;
use App\Infrastructure\Persistence\Readers\DatabaseImportedArticleReader;
use App\Infrastructure\Persistence\Repositories\ContentSourceRepository;
use App\Infrastructure\Persistence\Repositories\ImportRunRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Bindings for the Content Import context (epic #404).
 */
class ContentImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContentImportServiceInterface::class, ContentImportService::class);
        $this->app->bind(ContentSourceRepositoryInterface::class, ContentSourceRepository::class);
        $this->app->bind(ImportRunRepositoryInterface::class, ImportRunRepository::class);
        $this->app->bind(ImportedArticleReaderInterface::class, DatabaseImportedArticleReader::class);
        $this->app->bind(SystemAuthorProviderInterface::class, DatabaseSystemAuthorProvider::class);
        $this->app->bind(ContentSourceAdapterRegistryInterface::class, ConfigContentSourceAdapterRegistry::class);
        $this->app->bind(ArticleTaggerInterface::class, NullArticleTagger::class);
    }
}
