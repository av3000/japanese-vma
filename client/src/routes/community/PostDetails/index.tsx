import React, { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { POST_ROUTES, usePostQuery } from '@/api/posts/reads';
import { PageLoading } from '@/components/shared/PageLoading';
import PostContent from './PostContent';

/**
 * The contract resolves a transitional numeric identifier and always answers with the UUID, so a
 * legacy link is rewritten to the canonical address. Returning `null` once the URL already carries
 * the UUID is what keeps the replace to a single hop rather than a navigation loop.
 */
export const resolveCanonicalPostRedirect = (
	routeIdentifier: string | undefined,
	canonicalUuid: string | undefined,
): string | null => {
	if (!canonicalUuid || canonicalUuid === routeIdentifier) {
		return null;
	}

	return POST_ROUTES.detail(canonicalUuid);
};

const PostDetails: React.FC = () => {
	const { post_id: routeIdentifier } = useParams<{ post_id: string }>();
	const navigate = useNavigate();

	const { data: post, isLoading, isError } = usePostQuery(routeIdentifier);

	// The query seam has already seeded the UUID cache entry, so this replace does not refetch.
	const canonicalRedirect = resolveCanonicalPostRedirect(routeIdentifier, post?.uuid);

	useEffect(() => {
		if (!canonicalRedirect) {
			return;
		}

		navigate(canonicalRedirect, { replace: true });
	}, [canonicalRedirect, navigate]);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !post) {
		return (
			<div className="container mt-5 text-center">
				<p className="lead">Post not found or was deleted.</p>
				<a href={POST_ROUTES.list} className="btn btn-link">
					Back to Community
				</a>
			</div>
		);
	}

	return <PostContent post={post} />;
};

export default PostDetails;
