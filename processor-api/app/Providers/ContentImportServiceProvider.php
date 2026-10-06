<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\ContentImport\Interfaces\ArticleTaggerInterface;
use App\Application\ContentImport\Interfaces\ContentSourceAdapterRegistryInterface;
use App\Application\ContentImport\Interfaces\Providers\ImportSettingsProviderInterface;
use App\Application\ContentImport\Interfaces\Providers\SystemAuthorProviderInterface;
use App\Application\ContentImport\Interfaces\Repositories\ContentSourceRepositoryInterface;
use App\Application\ContentImport\Interfaces\Repositories\ImportRunRepositoryInterface;
use App\Application\ContentImport\Interfaces\Repositories\SourceTagMappingRepositoryInterface;
use App\Application\ContentImport\Services\ContentImportService;
use App\Application\ContentImport\Services\ContentImportServiceInterface;
use App\Application\ContentImport\Tagging\MappingArticleTagger;
use App\Infrastructure\ContentImport\ConfigContentSourceAdapterRegistry;
use App\Infrastructure\ContentImport\ConfigImportSettingsProvider;
use App\Infrastructure\ContentImport\DatabaseSystemAuthorProvider;
use App\Infrastructure\ContentImport\Http\PoliteHttpClient;
use App\Infrastructure\ContentImport\Http\RobotsTxtPolicy;
use App\Infrastructure\ContentImport\Sources\Nhk\NhkArticlePageParser;
use App\Infrastructure\ContentImport\Sources\Nhk\NhkNewsAdapter;
use App\Infrastructure\Persistence\Repositories\ContentSourceRepository;
use App\Infrastructure\Persistence\Repositories\ImportRunRepository;
use App\Infrastructure\Persistence\Repositories\SourceTagMappingRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
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
        $this->app->bind(ImportSettingsProviderInterface::class, ConfigImportSettingsProvider::class);
        $this->app->bind(SystemAuthorProviderInterface::class, DatabaseSystemAuthorProvider::class);
        $this->app->bind(ContentSourceAdapterRegistryInterface::class, ConfigContentSourceAdapterRegistry::class);
        $this->app->bind(SourceTagMappingRepositoryInterface::class, SourceTagMappingRepository::class);
        $this->app->bind(ArticleTaggerInterface::class, MappingArticleTagger::class);

        $this->app->bind(PoliteHttpClient::class, fn ($app): PoliteHttpClient => new PoliteHttpClient(
            $app->make(HttpFactory::class),
            (int) config('content_import.http.request_interval_ms', 1000),
            (int) config('content_import.http.timeout_seconds', 15),
        ));

        $this->app->bind(NhkNewsAdapter::class, function ($app): NhkNewsAdapter {
            // One client per adapter, shared with its robots policy: a run has one throttle and
            // one robots.txt cache across all of its requests.
            $http = $app->make(PoliteHttpClient::class);

            return new NhkNewsAdapter(
                $http,
                new RobotsTxtPolicy($http),
                $app->make(NhkArticlePageParser::class),
                (string) config('content_import.sources.'.NhkNewsAdapter::KEY.'.sitemap_url'),
            );
        });
    }
}
