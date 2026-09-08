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
import { useAuth } from '@/hooks/useAuth';
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
			<div className="container mt-5">
				<div className="row justify-content-center">
					<p>Posts could not be loaded. {error?.message}</p>
				</div>
			</div>
		);
	}

	const isBackgroundRefreshing = isFetching && !isFetchingNextPage;

	return (
		<div className="container mt-3">
			<div className="row justify-content-center">
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
			</div>

			{isAuthenticated && (
				<div className="row justify-content-center mt-3">
					<Link to={POST_ROUTES.create} className="tag-link">
						Create post
					</Link>
				</div>
			)}

			<div className="mt-2">
				<div className="col-10">
					{hasActiveFilters && (
						<>
							<Button variant="ghost" onClick={clearSearch}>
								<Icon name="broomSolid" /> Clear search
							</Button>
							<br />
							{filters.keyword && <h4>Results for: {filters.keyword}</h4>}
							{filters.hashtag && <h4>Tagged: #{filters.hashtag}</h4>}
						</>
					)}
					<h4>Results total: {total}</h4>
					{isBackgroundRefreshing && (
						<p role="status" className="text-muted">
							Refreshing results...
						</p>
					)}
				</div>

				<div className="my-3 p-3 bg-white rounded box-shadow">
					<hr />
					<div className="col-lg-12 col-md-10 mx-auto">
						{posts.length === 0 ? (
							<p>No posts found.</p>
						) : (
							posts.map((post) => (
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
							))
						)}
					</div>
				</div>
			</div>

			<div className="row justify-content-center">
				{hasNextPage ? (
					<Button
						variant="outline"
						className="w-50"
						onClick={() => void fetchNextPage()}
						disabled={isFetchingNextPage}
					>
						{isFetchingNextPage ? 'Loading more...' : 'Load More'}
					</Button>
				) : (
					<span className="text-muted">no more results...</span>
				)}
			</div>
		</div>
	);
};

export default PostsList;
