import React, { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { POST_ROUTES } from '@/api/posts/reads';
import {
	canDeletePost,
	canLockPost,
	canUpdatePost,
	nextLockRequest,
	readPostWriteError,
	useDeletePostMutation,
	useLockPostMutation,
} from '@/api/posts/writes';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { Button } from '@/components/shared/Button';
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
			onError: (error) => setStatus(readPostWriteError(error).message),
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
			onError: (error) => setStatus(readPostWriteError(error).message),
		});
	};

	return (
		<div className="d-flex align-items-center">
			{canLock && (
				<Button
					onClick={handleLock}
					variant="outline"
					size="md"
					hasOnlyIcon
					aria-label={isLocked ? 'Unlock this post' : 'Lock this post'}
					disabled={lockMutation.isPending}
				>
					<Icon size="md" name={isLocked ? 'lockSolid' : 'lockOpenSolid'} />
				</Button>
			)}

			{canDelete && (
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
			)}

			{canEdit && (
				<Button to={POST_ROUTES.edit(uuid)} variant="ghost" size="md" hasOnlyIcon aria-label="Edit this post">
					<Icon name="penSolid" size="md" />
				</Button>
			)}

			{status && (
				<span role="alert" className="text-danger ml-2">
					{status}
				</span>
			)}

			{canDelete && (
				<DeleteInstanceModal
					controller={deleteModal}
					instanceName={title}
					onDelete={handleDelete}
					isProcessing={deleteMutation.isPending}
					ariaLabel="Delete post"
				/>
			)}
		</div>
	);
};

export default PostOwnerActions;
