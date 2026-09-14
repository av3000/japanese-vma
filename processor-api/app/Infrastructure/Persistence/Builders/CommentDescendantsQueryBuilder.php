<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Builders;

use App\Domain\Shared\ValueObjects\Pagination;
use Illuminate\Support\Facades\DB;

/**
 * The reply-tree walk, in one place.
 *
 * Every read of a comment's subtree recurses over `parent_comment_id`, and the
 * repository and the thread reader both need it. Sharing the recursive fragment
 * here is what stops the two from drifting: a depth cap or an ordering fixed in
 * one copy but not the other would make counts disagree with the rows they
 * count.
 *
 * PostgreSQL-only, which the backend targets everywhere: recursive CTEs and
 * window functions are not expressible through the query builder.
 */
final readonly class CommentDescendantsQueryBuilder
{
    /**
     * Recursion guard for the reply walk.
     *
     * The schema puts no ceiling on nesting, so a cycle introduced by bad data
     * would otherwise spin forever inside the database. Ten levels is far past
     * anything the UI renders; rows deeper than that are counted out rather
     * than hanging the request.
     *
     * Read-side only. Deletion must reach every descendant regardless of depth,
     * so CommentRepository::collectDescendantIds deliberately does not use this
     * builder - capping a delete would orphan rows below the cap.
     */
    public const MAX_REPLY_DEPTH = 10;

    /**
     * Previews for many roots at once: one row per previewed reply, each
     * carrying its own root's whole subtree size.
     *
     * `$limitPerRoot` of zero means counts only. The rank limit is still at
     * least one, because the per-root total travels on a returned row and
     * dropping every row would drop the counts with it; the caller discards the
     * previews it did not ask for.
     *
     * @param int[] $rootIds
     *
     * @return array<int, object{id: int, root_id: int, total: int}>
     */
    public function rankedDescendants(array $rootIds, int $limitPerRoot): array
    {
        if ($rootIds === []) {
            return [];
        }

        $cte = $this->descendantsCte(count($rootIds));

        return DB::select(
            <<<SQL
            {$cte},
            ranked AS (
                SELECT
                    id,
                    root_id,
                    ROW_NUMBER() OVER (PARTITION BY root_id ORDER BY created_at ASC, id ASC) AS rn,
                    COUNT(*) OVER (PARTITION BY root_id) AS total
                FROM descendants
            )
            SELECT id, root_id, total
            FROM ranked
            WHERE rn <= ?
            SQL,
            [...array_values($rootIds), self::MAX_REPLY_DEPTH, max($limitPerRoot, 1)],
        );
    }

    /**
     * One page of a single root's subtree, oldest first, cut in the database.
     *
     * The window count rides along with the page so the size of the subtree and
     * the rows being returned come from one statement and cannot disagree.
     *
     * @return array{ids: int[], total: int}
     */
    public function pageOfDescendants(int $rootId, Pagination $pagination): array
    {
        $cte = $this->descendantsCte(1);

        $rows = DB::select(
            <<<SQL
            {$cte}
            SELECT id, COUNT(*) OVER () AS total
            FROM descendants
            -- A reply thread reads forwards, unlike the newest-first root list.
            ORDER BY created_at ASC, id ASC
            LIMIT ? OFFSET ?
            SQL,
            [
                $rootId,
                self::MAX_REPLY_DEPTH,
                $pagination->per_page,
                $pagination->offset(),
            ],
        );

        return [
            'ids' => array_map(static fn (object $row): int => (int) $row->id, $rows),
            'total' => (int) ($rows[0]->total ?? 0),
        ];
    }

    /**
     * The shared anchor and recursive step. Every caller binds the root ids
     * first, then the depth cap, then whatever its own tail needs.
     *
     * `root_id` is carried even for a single root so one fragment serves both
     * the per-root preview ranking and the single-thread page.
     *
     * Positional placeholders rather than a Postgres array literal: `?::int[]`
     * would put a `:int` sequence in front of PDO's placeholder scanner.
     */
    private function descendantsCte(int $rootCount): string
    {
        $rootPlaceholders = implode(',', array_fill(0, $rootCount, '?'));

        return <<<SQL
        WITH RECURSIVE descendants AS (
            SELECT c.id, c.parent_comment_id AS root_id, 1 AS depth, c.created_at
            FROM comments c
            WHERE c.parent_comment_id IN ({$rootPlaceholders})
            UNION ALL
            SELECT c.id, d.root_id, d.depth + 1, c.created_at
            FROM comments c
            INNER JOIN descendants d ON c.parent_comment_id = d.id
            WHERE d.depth < ?
        )
        SQL;
    }
}
