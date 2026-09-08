import React, { useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { POST_ROUTES, getPostDetailQueryKey } from '@/api/posts/reads';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';

interface PostOwnerActionsProps {
	postId: number;
	/** Route identifier the detail query is cached under. */
	detailIdentifier: string;
	title: string;
	authorId: number;
	isLocked: boolean;
}

/**
 * Transitional owner/admin controls for a Post.
 *
 * Post writes are owned by POST-WRITE-FE-01 (#138), so lock and delete still call the legacy numeric
 * endpoints. They live here rather than in the detail route so the read modules stay free of legacy
 * endpoint literals, and so #138 has one file to replace instead of a page to untangle.
 */
const PostOwnerActions: React.FC<PostOwnerActionsProps> = ({ postId, detailIdentifier, title, authorId, isLocked }) => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { user } = useAuth();

	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(deleteDialogRef, { id: 'post-delete-modal' });

	const lockMutation = useMutation({
		mutationFn: () => apiCall({ method: HttpMethod.POST, path: `/api/post/${postId}/toggleLock` }),
		onSuccess: () => {
			void queryClient.invalidateQueries({ queryKey: getPostDetailQueryKey(detailIdentifier) });
		},
		onError: (lockError) => {
			console.error('Toggling post lock failed', lockError);
		},
	});

	const deleteMutation = useMutation({
		mutationFn: () => apiCall({ method: HttpMethod.DELETE, path: `/api/post/${postId}` }),
		onSuccess: () => {
			deleteModal.close();
			navigate(POST_ROUTES.list);
		},
		onError: (deleteError) => {
			console.error('Deleting post failed', deleteError);
		},
	});

	const isOwner = user?.id === authorId;
	const isAdmin = Boolean(user?.isAdmin);

	if (!isOwner && !isAdmin) {
		return null;
	}

	return (
		<div className="d-flex align-items-center">
			{isAdmin && (
				<Button
					onClick={() => lockMutation.mutate()}
					variant="outline"
					size="md"
					hasOnlyIcon
					aria-label={isLocked ? 'Unlock this post' : 'Lock this post'}
					disabled={lockMutation.isPending}
				>
					<Icon size="md" name={isLocked ? 'lockSolid' : 'lockOpenSolid'} />
				</Button>
			)}

			{isOwner && (
				<>
					<Button
						onClick={deleteModal.open}
						variant="ghost"
						size="md"
						hasOnlyIcon
						aria-label="Delete this post"
						aria-controls={deleteModal.id}
						aria-expanded={deleteModal.isOpen}
					>
						<Icon name="trashbinSolid" size="md" />
					</Button>

					<Button
						to={POST_ROUTES.edit(postId)}
						variant="ghost"
						size="md"
						hasOnlyIcon
						aria-label="Edit this post"
					>
						<Icon name="penSolid" size="md" />
					</Button>
				</>
			)}

			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={title}
				onDelete={() => deleteMutation.mutate()}
				isProcessing={deleteMutation.isPending}
				ariaLabel="Delete post"
			/>
		</div>
	);
};

export default PostOwnerActions;
