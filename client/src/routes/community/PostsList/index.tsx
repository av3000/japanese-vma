import { useSearchParams } from 'react-router-dom';
import {
	POST_ROUTES,
	buildPostListSearchParams,
	parsePostListFilters,
	useInfinitePosts,
	type PostListFilterInput,
} from '@/api/posts/reads';
import PostItem from '@/components/features/community/PostItem';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { PageLoading } from '@/components/shared/PageLoading';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './PostsList.module.css';
import PostsSearchBar from './PostsSearchBar';

const PostsList = () => {
	const { isAuthenticated } = useAuth();
	const [searchParams, setSearchParams] = useSearchParams();
	const filters = parsePostListFilters(searchParams);
	const hasActiveFilters = Boolean(filters.keyword || filters.hashtag || filters.topic);

	const { posts, total, error, isPending, isError, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage } =
		useInfinitePosts({ filters });

	const handleSearch = (nextFilters: PostListFilterInput) => {
		setSearchParams(buildPostListSearchParams(nextFilters));
	};

	const clearSearch = () => {
		setSearchParams(new URLSearchParams());
	};

	// Only the very first load has nothing to show. Filter changes and background refetches keep the
	// previous page rendered instead of dropping back to this loader.
	if (isPending && posts.length === 0) {
		return <PageLoading family="list" />;
	}

	if (isError && posts.length === 0) {
		return (
			<Container size="md" className={styles.page}>
				<p className={styles.centered}>Posts could not be loaded. {error?.message}</p>
			</Container>
		);
	}

	const isBackgroundRefreshing = isFetching && !isFetchingNextPage;

	return (
		<Container size="md" className={styles.page}>
			<Stack gap="md">
				<PostsSearchBar
					// Remount the control when the URL changes so its inputs follow back/forward navigation.
					key={searchParams.toString()}
					defaults={{
						keyword: filters.keyword ?? '',
						topic: filters.topic ? String(filters.topic) : '',
						sort: filters.sort,
					}}
					onSearch={handleSearch}
				/>

				{isAuthenticated && (
					<div className={styles.centered}>
						<Link to={POST_ROUTES.create} className="tag-link">
							Create post
						</Link>
					</div>
				)}

				<Stack gap="xs" align="start" className={styles.results}>
					{hasActiveFilters && (
						<>
							<Button variant="ghost" onClick={clearSearch}>
								<Icon name="broomSolid" /> Clear search
							</Button>
							{filters.keyword && <h4>Results for: {filters.keyword}</h4>}
							{filters.hashtag && <h4>Tagged: #{filters.hashtag}</h4>}
						</>
					)}
					<h4>Results total: {total}</h4>
					{isBackgroundRefreshing && (
						<p role="status" className={styles.muted}>
							Refreshing results...
						</p>
					)}
				</Stack>

				<section className={styles.panel}>
					{posts.length === 0 ? (
						<p className={styles.empty}>No posts found.</p>
					) : (
						<ul className={styles.list}>
							{posts.map((post) => (
								<PostItem
									key={post.uuid}
									detailIdentifier={post.uuid}
									title={post.title}
									postType={post.topic_label}
									userName={post.authorName}
									date={post.formattedDate}
									commentsTotal={post.engagementCounts.comments}
									likesTotal={post.engagementCounts.likes}
									viewsTotal={post.engagementCounts.views}
									hashtags={post.hashtags.slice(0, 3)}
									isLocked={post.locked}
								/>
							))}
						</ul>
					)}
				</section>

				<Cluster justify="center">
					{hasNextPage ? (
						<Button
							variant="outline"
							className={styles.loadMore}
							onClick={() => void fetchNextPage()}
							disabled={isFetchingNextPage}
						>
							{isFetchingNextPage ? 'Loading more...' : 'Load More'}
						</Button>
					) : (
						<span className={styles.muted}>no more results...</span>
					)}
				</Cluster>
			</Stack>
		</Container>
	);
};

export default PostsList;
