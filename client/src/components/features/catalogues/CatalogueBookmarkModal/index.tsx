import { Link } from 'react-router-dom';
import { Button } from '@/components/shared/Button';
import { DialogModal, type DialogModalSize } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';
import { LIST_ACTIONS } from '@/shared/constants';

interface BookmarkListItem {
	id: number;
	title: string;
	elementBelongsToList?: boolean;
}

interface CatalogueBookmarkModalProps {
	controller: ModalController;
	lists: BookmarkListItem[];
	loadingListIds: number[];
	onListAction: (listId: number, action: string) => void;
	title?: string;
	emptyText?: string;
	createListHref?: string;
	ariaLabel?: string;
	size?: DialogModalSize;
}

export const CatalogueBookmarkModal = ({
	controller,
	lists,
	loadingListIds,
	onListAction,
	title = 'Save to List',
	emptyText = 'You have no lists created.',
	createListHref = '/newlist',
	ariaLabel = 'Save to List',
	size = 'md',
}: CatalogueBookmarkModalProps) => {
	if (!controller.isRendered) return null;

	return (
		<DialogModal {...controller.dialogProps} size={size} ariaLabel={ariaLabel}>
			<DialogModal.Header>
				<DialogModal.Title>{title}</DialogModal.Title>
			</DialogModal.Header>
			<DialogModal.Body>
				{lists.length === 0 && <p className="text-muted">{emptyText}</p>}
				{lists.map((list) => {
					const isActive = Boolean(list.elementBelongsToList);
					const action = isActive ? LIST_ACTIONS.REMOVE_ITEM : LIST_ACTIONS.ADD_ITEM;
					const isLoading = loadingListIds.includes(list.id);

					return (
						<div key={list.id} className="d-flex justify-content-between align-items-center mb-2">
							<Link to={`/list/${list.id}`}>{list.title}</Link>
							<Button
								variant={isActive ? 'danger' : 'primary'}
								size="sm"
								onClick={() => onListAction(list.id, action)}
								disabled={isLoading}
							>
								{isLoading ? (
									<span className="spinner-border spinner-border-sm" />
								) : isActive ? (
									'Remove'
								) : (
									'Add'
								)}
							</Button>
						</div>
					);
				})}
				<div className="mt-3 text-right">
					<Link to={createListHref} className="small">
						+ Create a new list
					</Link>
				</div>
			</DialogModal.Body>
		</DialogModal>
	);
};
