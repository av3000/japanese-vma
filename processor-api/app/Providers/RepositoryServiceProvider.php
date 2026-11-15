<?php

namespace App\Providers;

use App\Application\Activity\ActivitySourceContext;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\AdminDashboard\Interfaces\Caches\AdminDashboardCacheInterface;
use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Comments\Interfaces\Readers\CommentThreadReaderInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;

use App\Application\Engagement\Interfaces\Readers\EntityEngagementReaderInterface;
use App\Application\Engagement\Interfaces\Readers\EntityReferenceReaderInterface;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeTargetRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Application\JapaneseMaterial\Radicals\Interfaces\Repositories\RadicalRepositoryInterface;
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Application\JapaneseMaterial\Stats\Interfaces\Caches\CorpusStatsCacheInterface;
use App\Application\JapaneseMaterial\Stats\Interfaces\Readers\CorpusStatsReaderInterface;
use App\Application\JapaneseMaterial\Words\Interfaces\Repositories\WordRepositoryInterface;
use App\Application\Operations\Interfaces\Caches\QueueWorkerMarkerStoreInterface;
use App\Application\Operations\Interfaces\Readers\ImportStatusReaderInterface;
use App\Application\Operations\Services\EnvironmentHealthService;
use App\Application\Operations\Services\EnvironmentHealthServiceInterface;
use App\Application\Operations\Services\HealthCheckRunner;
use App\Application\Operations\Services\QueueWorkerVerifier;
use App\Application\Operations\Services\QueueWorkerVerifierInterface;
use App\Application\Processing\Interfaces\Readers\ProcessingOwnerResolverInterface;
use App\Application\Processing\Interfaces\Repositories\ProcessingStateRepositoryInterface;
use App\Application\Search\Interfaces\Caches\SearchCountCacheInterface;
use App\Application\Study\Interfaces\Readers\DistractorPoolReaderInterface;
use App\Application\Study\Interfaces\Repositories\StudySessionRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\UserAccountRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Domain\Activity\Enums\ActivitySource;
use App\Infrastructure\Operations\CacheQueueWorkerMarkerStore;
use App\Infrastructure\Operations\HealthChecks\DatabaseHealthCheck;
use App\Infrastructure\Operations\HealthChecks\RedisHealthCheck;
use App\Infrastructure\Operations\HealthChecks\ReverbConfiguredHealthCheck;
use App\Infrastructure\Operations\HealthChecks\SentryConfiguredHealthCheck;
use App\Infrastructure\Persistence\Readers\CachedAdminDashboardReader;
use App\Infrastructure\Persistence\Readers\CachedCorpusStatsReader;
use App\Infrastructure\Persistence\Readers\DatabaseAdminDashboardReader;
use App\Infrastructure\Persistence\Readers\DatabaseArticleListReader;
use App\Infrastructure\Persistence\Readers\DatabaseArticleProcessingStateReader;
use App\Infrastructure\Persistence\Readers\DatabaseCommentThreadReader;
use App\Infrastructure\Persistence\Readers\DatabaseCorpusStatsReader;
use App\Infrastructure\Persistence\Readers\DatabaseDistractorPoolReader;
use App\Infrastructure\Persistence\Readers\DatabaseEntityEngagementReader;
use App\Infrastructure\Persistence\Readers\DatabaseEntityReferenceReader;
use App\Infrastructure\Persistence\Readers\DatabaseImportStatusReader;
use App\Infrastructure\Persistence\Readers\DatabaseProcessingOwnerResolver;
use App\Infrastructure\Persistence\Repositories\ArticleRepository;
use App\Infrastructure\Persistence\Repositories\CatalogueItemRepository;
use App\Infrastructure\Persistence\Repositories\CatalogueRepository;
use App\Infrastructure\Persistence\Repositories\CommentRepository;
use App\Infrastructure\Persistence\Repositories\DatabaseActivityRecorder;
use App\Infrastructure\Persistence\Repositories\DownloadRepository;
use App\Infrastructure\Persistence\Repositories\HashtagRepository;
use App\Infrastructure\Persistence\Repositories\KanjiRepository;
use App\Infrastructure\Persistence\Repositories\LikeRepository;
use App\Infrastructure\Persistence\Repositories\LikeTargetRepository;
use App\Infrastructure\Persistence\Repositories\PostRepository;
use App\Infrastructure\Persistence\Repositories\ProcessingStateRepository;
use App\Infrastructure\Persistence\Repositories\RadicalRepository;
use App\Infrastructure\Persistence\Repositories\RoleRepository;
use App\Infrastructure\Persistence\Repositories\SentenceRepository;
use App\Infrastructure\Persistence\Repositories\StudySessionRepository;
use App\Infrastructure\Persistence\Repositories\UserAccountRepository;
use App\Infrastructure\Persistence\Repositories\UserRepository;
use App\Infrastructure\Persistence\Repositories\ViewRepository;
use App\Infrastructure\Persistence\Repositories\WordRepository;
use App\Infrastructure\Search\CacheSearchCountCache;
use App\Support\JapaneseDataImport\JapaneseDataManifest;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProcessingStateRepositoryInterface::class, ProcessingStateRepository::class);
        $this->app->bind(ProcessingOwnerResolverInterface::class, DatabaseProcessingOwnerResolver::class);
        // Console runs and queued jobs default to Console; requests are marked by their surface.
        $this->app->scoped(ActivitySourceContext::class, static fn ($app): ActivitySourceContext => new ActivitySourceContext(
            $app->runningInConsole() ? ActivitySource::Console : null,
        ));
        $this->app->bind(ActivityRecorderInterface::class, static fn ($app): DatabaseActivityRecorder => new DatabaseActivityRecorder(
            $app->make(ActivitySourceContext::class),
            strict: ! $app->isProduction(),
        ));
        $this->app->bind(UserAccountRepositoryInterface::class, UserAccountRepository::class);
        $this->app->bind(EnvironmentHealthServiceInterface::class, EnvironmentHealthService::class);
        $this->app->bind(QueueWorkerVerifierInterface::class, QueueWorkerVerifier::class);
        $this->app->bind(QueueWorkerMarkerStoreInterface::class, CacheQueueWorkerMarkerStore::class);
        $this->app->bind(SearchCountCacheInterface::class, CacheSearchCountCache::class);
        // Order is display order on the health page.
        $this->app->bind(HealthCheckRunner::class, static fn ($app): HealthCheckRunner => new HealthCheckRunner([
            $app->make(DatabaseHealthCheck::class),
            $app->make(RedisHealthCheck::class),
            $app->make(ReverbConfiguredHealthCheck::class),
            $app->make(SentryConfiguredHealthCheck::class),
        ]));
        $this->app->bind(ImportStatusReaderInterface::class, fn ($app): DatabaseImportStatusReader => new DatabaseImportStatusReader(
            new JapaneseDataManifest(),
            $app->make('cache.store'),
        ));
        $this->app->bind(EntityReferenceReaderInterface::class, DatabaseEntityReferenceReader::class);
        $this->app->bind(EntityEngagementReaderInterface::class, DatabaseEntityEngagementReader::class);

        $this->app->singleton(
            ArticleRepositoryInterface::class,
            ArticleRepository::class
        );

        // Article list read ports. Swapping in a search-engine reader later means
        // rebinding ArticleListReaderInterface here and nothing else.
        $this->app->singleton(
            ArticleListReaderInterface::class,
            DatabaseArticleListReader::class
        );

        $this->app->singleton(
            ArticleProcessingStateReaderInterface::class,
            DatabaseArticleProcessingStateReader::class
        );

        $this->app->singleton(
            PostRepositoryInterface::class,
            PostRepository::class
        );

        $this->app->singleton(
            KanjiRepositoryInterface::class,
            KanjiRepository::class
        );

        $this->app->singleton(
            RadicalRepositoryInterface::class,
            RadicalRepository::class
        );

        $this->app->singleton(
            SentenceRepositoryInterface::class,
            SentenceRepository::class
        );

        $this->app->singleton(
            WordRepositoryInterface::class,
            WordRepository::class
        );

        // Corpus totals for the landing page. One cached instance serves both the read port and
        // the invalidation port, so the import command clears exactly the key the reader fills.
        $this->app->singleton(
            CachedCorpusStatsReader::class,
            fn ($app): CachedCorpusStatsReader => new CachedCorpusStatsReader(
                new DatabaseCorpusStatsReader(),
                $app->make('cache.store'),
            )
        );
        $this->app->alias(CachedCorpusStatsReader::class, CorpusStatsReaderInterface::class);
        $this->app->alias(CachedCorpusStatsReader::class, CorpusStatsCacheInterface::class);

        $this->app->singleton(
            CachedAdminDashboardReader::class,
            fn ($app): CachedAdminDashboardReader => new CachedAdminDashboardReader(
                new DatabaseAdminDashboardReader(),
                $app->make('cache.store'),
            )
        );
        $this->app->alias(CachedAdminDashboardReader::class, AdminDashboardReaderInterface::class);
        $this->app->alias(CachedAdminDashboardReader::class, AdminDashboardCacheInterface::class);

        $this->app->singleton(
            CommentRepositoryInterface::class,
            CommentRepository::class
        );

        // Wrong-answer candidates for flashcards in options mode (epic #413).
        $this->app->singleton(
            DistractorPoolReaderInterface::class,
            DatabaseDistractorPoolReader::class
        );

        $this->app->singleton(
            StudySessionRepositoryInterface::class,
            StudySessionRepository::class
        );

        // Comment thread read port. The repository keeps identity reads and
        // writes; everything paginated goes through here.
        $this->app->singleton(
            CommentThreadReaderInterface::class,
            DatabaseCommentThreadReader::class
        );

        $this->app->singleton(
            ViewRepositoryInterface::class,
            ViewRepository::class
        );

        $this->app->singleton(
            LikeRepositoryInterface::class,
            LikeRepository::class
        );

        $this->app->singleton(
            LikeTargetRepositoryInterface::class,
            LikeTargetRepository::class
        );

        $this->app->singleton(
            DownloadRepositoryInterface::class,
            DownloadRepository::class
        );

        $this->app->singleton(
            HashtagRepositoryInterface::class,
            HashtagRepository::class
        );

        $this->app->singleton(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->singleton(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );

        $this->app->singleton(
            CatalogueRepositoryInterface::class,
            CatalogueRepository::class
        );

        $this->app->singleton(
            CatalogueItemRepositoryInterface::class,
            CatalogueItemRepository::class
        );
    }
}
