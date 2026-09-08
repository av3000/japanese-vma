import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toggleInstanceLike } from '@/api/likes/likes';
import { getPostDetailQueryKey } from '@/api/posts/reads';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { useAuth } from '@/hooks/useAuth';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';

interface PostLikeButtonProps {
	postId: number;
	/** Route identifier the detail query is cached under, so a like refreshes the visible Post. */
	detailIdentifier: string;
	likesCount: number;
}

/**
 * Transitional Post like control.
 *
 * The v1 Post read contract carries aggregate counts only - `is_liked_by_viewer` is owned by the
 * Like slice (LIKE-FE-01, #144). Until that lands the button toggles through the generic v1
 * `like-instance` endpoint and refetches the count, without the filled/unfilled viewer state.
 */
const PostLikeButton: React.FC<PostLikeButtonProps> = ({ postId, detailIdentifier, likesCount }) => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { isAuthenticated } = useAuth();

	const likeMutation = useMutation({
		mutationFn: () =>
			toggleInstanceLike({
				objectType: ObjectTemplateTypeLabel[ObjectTemplateType.POST],
				objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.POST],
				instanceId: postId,
			}),
		onSuccess: () => {
			void queryClient.invalidateQueries({ queryKey: getPostDetailQueryKey(detailIdentifier) });
		},
		onError: (likeError) => {
			console.error('Like post failed', likeError);
		},
	});

	const handleClick = () => {
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		likeMutation.mutate();
	};

	return (
		<div className="d-flex align-items-center">
			<p className="mb-0 mr-2">{likesCount} likes</p>
			<Button
				variant="ghost"
				hasOnlyIcon
				aria-label="Like this post"
				onClick={handleClick}
				disabled={likeMutation.isPending}
			>
				<Icon size="md" name="thumbsUpSolid" />
			</Button>
		</div>
	);
};

export default PostLikeButton;
