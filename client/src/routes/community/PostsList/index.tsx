import { useSearchParams } from 'react-router-dom';
import {
	POST_ROUTES,
	buildPostListSearchParams,
	parsePostListFilters,
	useInfinitePosts,
	type PostListFilterInput,
	type PostListFilters,
} from '@/api/posts/reads';
import Spinner from '@/assets/images/spinner.gif';
import { LibraryEmptyState, LibraryPage } from '@/components/features/LibraryCards/LibraryLayout';
import PostCard from '@/components/features/community/PostCard';
import PostFilters from '@/components/features/community/PostFilters';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { PageHeader } from '@/components/shared/PageHeader';
import { PageLoading } from '@/components/shared/PageLoading';
import { Cluster } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './PostsList.module.css';

/** What the empty list says: a search, other filters, or no posts at all. */
export const emptyState = (filters: PostListFilters) => {
	if (filters.keyword) {
		return { title: 'No posts match', term: filters.keyword, hint: 'Try a shorter search, or clear the filters.' };
	}

	if (filters.hashtag || filters.topic) {
		return { title: 'No posts match these filters', hint: 'Try another topic or tag, or clear the filters.' };
	}

	return { title: 'No posts yet', hint: 'Questions, feedback and announcements from the community appear here.' };
};

/** One muted line under the title: how much is shown, and which search or tag produced it. */
export const listMeta = (filters: PostListFilters, shown: number, total: number) =>
	[
		`Showing ${shown} of ${total}`,
		filters.keyword && `Results for: ${filters.keyword}`,
		filters.hashtag && `Tagged #${filters.hashtag}`,
	]
		.filter(Boolean)
		.join(' · ');

/**
 * Community posts as Library Card rows. The URL owns every filter, so refresh, deep links and
 * back/forward reproduce what is on screen.
 */
const PostsList = () => {
	const { isAuthenticated } = useAuth();
	const [searchParams, setSearchParams] = useSearchParams();
	const filters = parsePostListFilters(searchParams);

	const { posts, total, isPending, isError, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage } =
		useInfinitePosts({ filters });

	const handleChange = (next: PostListFilterInput) => setSearchParams(buildPostListSearchParams(next));
	const handleReset = () => setSearchParams(new URLSearchParams());

	const newPostAction = isAuthenticated ? (
		<Button to={POST_ROUTES.create} variant="primary">
			New post
		</Button>
	) : undefined;

	// Only the very first load has nothing to show. Filter changes and background refetches keep the
	// previous page rendered instead of dropping back to this loader.
	if (isPending && posts.length === 0) {
		return (
			<LibraryPage>
				<PageHeader title="Community" action={newPostAction} />
				<PageLoading family="list" />
			</LibraryPage>
		);
	}

	if (isError && posts.length === 0) {
		return (
			<LibraryPage>
				<PageHeader title="Community" action={newPostAction} />
				<Alert tone="danger">Posts couldn't be loaded. Please try again.</Alert>
			</LibraryPage>
		);
	}

	const isBackgroundRefreshing = isFetching && !isFetchingNextPage;

	return (
		<LibraryPage>
			<PageHeader title="Community" meta={listMeta(filters, posts.length, total)} action={newPostAction} />

			<PostFilters filters={filters} onChange={handleChange} onReset={handleReset} />

			{isBackgroundRefreshing && (
				<p role="status" className={styles.muted}>
					Refreshing results…
				</p>
			)}
			{isError ? <Alert tone="danger">More posts couldn't be loaded. Please try again.</Alert> : null}

			{posts.length === 0 ? (
				<LibraryEmptyState {...emptyState(filters)} />
			) : (
				<ul className={styles.list}>
					{posts.map((post) => (
						<li key={post.uuid}>
							<PostCard post={post} />
						</li>
					))}
				</ul>
			)}

			{/* The empty state already says there is nothing; "No more results" under it is noise. */}
			{posts.length > 0 && (
				<Cluster justify="center" className={styles.loadMore}>
					{isFetchingNextPage ? (
						<img src={Spinner} alt="Loading more..." className={styles.loadMoreSpinner} />
					) : hasNextPage ? (
						<Button
							variant="secondary-outline"
							className={styles.loadMoreButton}
							onClick={() => void fetchNextPage()}
						>
							Load More
						</Button>
					) : (
						<span className={styles.muted}>No more results</span>
					)}
				</Cluster>
			)}
		</LibraryPage>
	);
};

export default PostsList;
