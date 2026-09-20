import {
	getArticleIndexQueryKey,
	getArticleKanjisQueryKey,
	getArticleShowQueryKey,
	getArticleWordsQueryKey,
} from '@/api/generated/article/article';
import type { ArticleIndexParams } from '@/api/generated/model/articleIndexParams';
import type { ArticleKanjisParams } from '@/api/generated/model/articleKanjisParams';
import type { ArticleShowParams } from '@/api/generated/model/articleShowParams';
import type { ArticleWordsParams } from '@/api/generated/model/articleWordsParams';

/**
 * The one place article query keys are spelled.
 *
 * Every key is derived from the generated helpers so a hand-written literal can never drift
 * away from what the transport actually caches under (audit finding F-05: the subscription
 * patched a bare "articles" key while the list lived under the generated key, so list badges
 * never updated and freshly created articles were missing from the list).
 *
 * `kanjis(uuid)` and `words(uuid)` are the attachment pages the detail page reads since #268;
 * passing no params gives the prefix every page of that list shares, which is what a targeted
 * invalidation uses.
 *
 * `lists()` is the prefix shared by every list variant and is what invalidations target.
 * `list(params)` is the exact key one `useInfiniteArticles` call reads. `detail(uuid)` is
 * the single-article key used by the detail query, the like and status mutations, the edit
 * modal and the processing-status subscription.
 */
export const articleKeys = {
	lists: () => getArticleIndexQueryKey(),
	list: (params?: ArticleIndexParams) => getArticleIndexQueryKey(params),
	detail: (uuid: string, params?: ArticleShowParams) => getArticleShowQueryKey(uuid, params),
	kanjis: (uuid: string, params?: ArticleKanjisParams) => getArticleKanjisQueryKey(uuid, params),
	words: (uuid: string, params?: ArticleWordsParams) => getArticleWordsQueryKey(uuid, params),
} as const;

export type ArticleListQueryKey = ReturnType<typeof articleKeys.list>;
export type ArticleDetailQueryKey = ReturnType<typeof articleKeys.detail>;
