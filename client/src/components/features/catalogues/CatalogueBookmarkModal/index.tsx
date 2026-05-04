import { Link } from 'react-router-dom';
import type { CatalogueBookmarkListItem, CatalogueMembershipAction } from '@/api/catalogues/bookmarkMembership';
import { Button } from '@/components/shared/Button';
import { DialogModal, type DialogModalSize } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';

interface CatalogueBookmarkModalProps {
	controller: ModalController;
	lists: CatalogueBookmarkListItem[];
	loadingListIds: number[];
	onListAction: (list: CatalogueBookmarkListItem, action: CatalogueMembershipAction) => void;
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
	createListHref = CATALOGUE_ROUTES.create,
	ariaLabel = 'Save to List',
	size = 'md',
}: CatalogueBookmarkModalProps) => {
	if (!controller.isRendered) return null;

	return (
		<DialogModal
			id={controller.id}
			dialogRef={controller.dialogRef}
			isOpen={controller.isOpen}
			onClose={controller.close}
			size={size}
			ariaLabel={ariaLabel}
		>
			<DialogModal.Header>
				<DialogModal.Title>{title}</DialogModal.Title>
			</DialogModal.Header>
			<DialogModal.Body>
				{lists.length === 0 && <p className="text-muted">{emptyText}</p>}
				{lists.map((list) => {
					const isActive = Boolean(list.elementBelongsToList);
					const action: CatalogueMembershipAction = isActive ? 'remove' : 'add';
					const isLoading = loadingListIds.includes(list.id);

					return (
						<div key={list.id} className="d-flex justify-content-between align-items-center mb-2">
							<Link to={CATALOGUE_ROUTES.detail(list.uuid)}>{list.title}</Link>
							<Button
								variant={isActive ? 'danger' : 'primary'}
								size="sm"
								onClick={() => onListAction(list, action)}
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
