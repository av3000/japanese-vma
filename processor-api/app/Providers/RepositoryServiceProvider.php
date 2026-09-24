<?php

namespace App\Providers;

use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Comments\Interfaces\Readers\CommentThreadReaderInterface;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
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
use App\Application\Processing\Interfaces\Readers\ProcessingOwnerResolverInterface;
use App\Application\Processing\Interfaces\Repositories\ProcessingStateRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\RoleRepositoryInterface;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Readers\CachedCorpusStatsReader;
use App\Infrastructure\Persistence\Readers\DatabaseArticleListReader;
use App\Infrastructure\Persistence\Readers\DatabaseArticleProcessingStateReader;
use App\Infrastructure\Persistence\Readers\DatabaseCommentThreadReader;
use App\Infrastructure\Persistence\Readers\DatabaseCorpusStatsReader;
use App\Infrastructure\Persistence\Readers\DatabaseProcessingOwnerResolver;
use App\Infrastructure\Persistence\Repositories\ArticleRepository;
use App\Infrastructure\Persistence\Repositories\CatalogueItemRepository;
use App\Infrastructure\Persistence\Repositories\CatalogueRepository;
use App\Infrastructure\Persistence\Repositories\CommentRepository;
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
use App\Infrastructure\Persistence\Repositories\UserRepository;
use App\Infrastructure\Persistence\Repositories\ViewRepository;
use App\Infrastructure\Persistence\Repositories\WordRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProcessingStateRepositoryInterface::class, ProcessingStateRepository::class);
        $this->app->bind(ProcessingOwnerResolverInterface::class, DatabaseProcessingOwnerResolver::class);

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
            CommentRepositoryInterface::class,
            CommentRepository::class
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
