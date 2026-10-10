import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useLikePostMutation } from '@/api/posts/likes';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { useAuth } from '@/hooks/useAuth';

const countFormat = new Intl.NumberFormat('en-US');

interface PostLikeButtonProps {
	postId: number;
	/** Route identifier the detail query is cached under, so a like patches the visible Post. */
	detailIdentifier: string;
	likesCount: number;
}

/**
 * Post like control.
 *
 * Unlike Article, Catalogue and Comment, the v1 Post read contract carries aggregate counts only
 * and no `is_liked_by_viewer`. There is therefore no persisted viewer state to render on load: the
 * filled icon reflects the last toggle this session returned, and the count comes from the cached
 * Post that the mutation patches on success.
 */
const PostLikeButton: React.FC<PostLikeButtonProps> = ({ postId, detailIdentifier, likesCount }) => {
	const navigate = useNavigate();
	const { isAuthenticated } = useAuth();
	const likeMutation = useLikePostMutation(detailIdentifier);

	const isLiked = likeMutation.data?.is_liked ?? false;

	const handleClick = () => {
		// The endpoint answers an anonymous caller with a 401, so the login redirect happens here
		// rather than as error handling after a pointless request.
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		likeMutation.mutate(postId);
	};

	// A rail action, as on Article and Catalogue detail: full width, the count in the visible label.
	return (
		<Button
			variant="outline"
			isFullWidth
			aria-pressed={isLiked}
			onClick={handleClick}
			disabled={likeMutation.isTogglingInstance(postId)}
		>
			<Icon size="sm" name={isLiked ? 'thumbsUpSolid' : 'thumbsUpRegular'} />
			{`Like · ${countFormat.format(likesCount)}`}
		</Button>
	);
};

export default PostLikeButton;
