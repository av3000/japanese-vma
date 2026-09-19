import { Link } from 'react-router-dom';
import type { CatalogueForItem, CatalogueForItemAction } from '@/api/catalogues/cataloguesForItem';
import { Button } from '@/components/shared/Button';
import { DialogModal, type DialogModalSize } from '@/components/shared/DialogModal';
import { Cluster, Stack } from '@/components/shared/layout';
import type { ModalController } from '@/hooks/useModal';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import styles from './CatalogueBookmarkModal.module.css';

interface CatalogueBookmarkModalProps {
	controller: ModalController;
	lists: CatalogueForItem[];
	loadingListIds: number[];
	onListAction: (list: CatalogueForItem, action: CatalogueForItemAction) => void;
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
				{lists.length === 0 ? (
					<p className={styles.empty}>{emptyText}</p>
				) : (
					<Stack as="ul" gap="xs" className={styles.list}>
						{lists.map((list) => {
							const isActive = list.contains_item;
							const action: CatalogueForItemAction = isActive ? 'remove' : 'add';
							const isLoading = loadingListIds.includes(list.id);

							return (
								<Cluster as="li" key={list.id} justify="between" gap="sm">
									<Link to={CATALOGUE_ROUTES.detail(list.uuid)}>{list.title}</Link>
									<Button
										variant={isActive ? 'danger' : 'primary'}
										size="sm"
										onClick={() => onListAction(list, action)}
										disabled={isLoading}
										isLoading={isLoading}
									>
										{isActive ? 'Remove' : 'Add'}
									</Button>
								</Cluster>
							);
						})}
					</Stack>
				)}
				<p className={styles.createLink}>
					<Link to={createListHref}>+ Create a new list</Link>
				</p>
			</DialogModal.Body>
		</DialogModal>
	);
};
