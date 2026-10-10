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
	/** The viewer's own like state from the cached Post, which the toggle patches optimistically. */
	isLiked: boolean;
}

/**
 * Post like control. Both the pressed state and the count come from the cached Post detail, so a
 * reload shows the viewer's earlier like and a toggle shows at once.
 */
const PostLikeButton: React.FC<PostLikeButtonProps> = ({ postId, detailIdentifier, likesCount, isLiked }) => {
	const navigate = useNavigate();
	const { isAuthenticated } = useAuth();
	const likeMutation = useLikePostMutation(detailIdentifier);

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
