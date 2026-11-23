import React, { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { parseApiError } from '@/api/apiError';
import { POST_ROUTES } from '@/api/posts/reads';
import {
	canDeletePost,
	canLockPost,
	canUpdatePost,
	nextLockRequest,
	useDeletePostMutation,
	useLockPostMutation,
} from '@/api/posts/writes';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { DetailActionGroup } from '@/components/shared/DetailActions';
import { Icon } from '@/components/shared/Icon';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';

interface PostOwnerActionsProps {
	postId: number;
	/** Canonical identifier every write addresses. */
	uuid: string;
	title: string;
	authorId: number;
	isLocked: boolean;
}

/**
 * Owner/admin controls for a Post, kept out of the detail route so that surface stays a pure read.
 *
 * The three gates differ and are not interchangeable: editing is owner-only, deleting is owner or
 * admin, locking is admin-only. They mirror `PostPolicy`, which stays authoritative — this only
 * decides which controls are worth rendering.
 */
const PostOwnerActions: React.FC<PostOwnerActionsProps> = ({ postId, uuid, title, authorId, isLocked }) => {
	const navigate = useNavigate();
	const { user } = useAuth();

	const [status, setStatus] = useState<string | null>(null);

	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(deleteDialogRef, { id: 'post-delete-modal' });

	const post = { id: postId, uuid };

	const lockMutation = useLockPostMutation(post);
	const deleteMutation = useDeletePostMutation(post);

	const canEdit = canUpdatePost(user, authorId);
	const canDelete = canDeletePost(user, authorId);
	const canLock = canLockPost(user);

	if (!canEdit && !canDelete && !canLock) {
		return null;
	}

	const handleLock = () => {
		setStatus(null);

		// The desired state, not a toggle: a retried request cannot flip the Post back.
		lockMutation.mutate(nextLockRequest(isLocked), {
			onError: (error) => setStatus(parseApiError(error).message),
		});
	};

	const handleDelete = () => {
		setStatus(null);

		deleteMutation.mutate(undefined, {
			onSuccess: () => {
				deleteModal.close();
				navigate(POST_ROUTES.list);
			},
			// The dialog stays open on failure so the reason is visible next to the action that failed.
			onError: (error) => setStatus(parseApiError(error).message),
		});
	};

	const deleteButton = (
		<Button
			onClick={deleteModal.open}
			variant="outline"
			isFullWidth
			aria-controls={deleteModal.id}
			aria-expanded={deleteModal.isOpen}
		>
			<Icon name="trashbinSolid" size="sm" />
			Delete post
		</Button>
	);

	// The author's own controls sit under "Your post"; an admin's under "Moderation". An admin who
	// is also the author gets Delete once, under "Your post".
	return (
		<>
			{canEdit && (
				<DetailActionGroup heading="Your post">
					<Button to={POST_ROUTES.edit(uuid)} variant="outline" isFullWidth>
						<Icon name="penSolid" size="sm" />
						Edit post
					</Button>
					{canDelete && deleteButton}
				</DetailActionGroup>
			)}

			{(canLock || (canDelete && !canEdit)) && (
				<DetailActionGroup heading="Moderation">
					{canLock && (
						<Button
							onClick={handleLock}
							variant="outline"
							isFullWidth
							isLoading={lockMutation.isPending}
							disabled={lockMutation.isPending}
						>
							<Icon size="sm" name={isLocked ? 'lockOpenSolid' : 'lockSolid'} />
							{isLocked ? 'Unlock post' : 'Lock post'}
						</Button>
					)}
					{canDelete && !canEdit && deleteButton}
				</DetailActionGroup>
			)}

			{status && <Alert tone="danger">{status}</Alert>}

			{canDelete && (
				<DeleteInstanceModal
					controller={deleteModal}
					instanceName={title}
					onDelete={handleDelete}
					isProcessing={deleteMutation.isPending}
					deleteLabel="Yes, Delete Post"
					ariaLabel="Delete post"
				/>
			)}
		</>
	);
};

export default PostOwnerActions;
