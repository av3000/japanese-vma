import { useEffect, useRef, useState } from 'react';
import {
	optimisticApplyCatalogueForItemAction,
	CatalogueForItem,
	CatalogueForItemAction,
	fetchCataloguesForItem,
	addOrRemoveCatalogueForItem,
} from '@/api/catalogues/cataloguesForItem';
import { CatalogueBookmarkModal } from '@/components/features/catalogues/CatalogueBookmarkModal';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { useModal } from '@/hooks/useModal';
import { SavedListType } from '@/shared/constants/enums';

interface AuthorizedBookmarkWidgetProps {
	entityId: number; // TODO: Might consider uuid, but maybe it doesnt make a difference.
	instanceObjectType: SavedListType;
	isKnownType: SavedListType;
	modalTitle?: string;
}

export const AuthorizedBookmarkWidget: React.FC<AuthorizedBookmarkWidgetProps> = ({
	entityId,
	instanceObjectType,
	isKnownType,
	modalTitle = 'Choose Sentence List to add',
}) => {
	const [lists, setLists] = useState<CatalogueForItem[]>([]);
	const [isBookmarked, setIsBookmarked] = useState(false);
	const [isKnown, setIsKnown] = useState(false);
	const [isLoadingUserCatalogues, setIsLoadingUserCatalogues] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState<number[]>([]);
	const bookmarkDialogRef = useRef<HTMLDialogElement | null>(null);
	const bookmarkModal = useModal(bookmarkDialogRef, { id: 'sentence-bookmark-modal' });

	useEffect(() => {
		void getUserCatalogues();
	}, [entityId]);

	const addToOrRemoveFromList = async (list: CatalogueForItem, action: CatalogueForItemAction) => {
		if (Number.isNaN(entityId)) {
			return;
		}

		setLoadingListIds((prev) => [...prev, list.id]);
		try {
			await addOrRemoveCatalogueForItem({
				list,
				elementId: entityId,
				action,
			});

			// TODO: This could become some mapper perhaps?
			setLists((prevLists) => {
				const updatedLists = optimisticApplyCatalogueForItemAction(prevLists, list.id, action);
				// since widget should be generic, no need for specific objectTemplate type.
				setIsBookmarked(updatedLists.some((list) => list.contains_item));
				setIsKnown(updatedLists.some((list) => list.contains_item && list.type === isKnownType));
				return updatedLists;
			});
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== list.id));
		}
	};

	const getUserCatalogues = async () => {
		if (!entityId) {
			return;
		}

		setIsLoadingUserCatalogues(true);
		try {
			const updatedLists = await fetchCataloguesForItem(entityId, {
				types: [instanceObjectType, isKnownType],
			});

			setIsBookmarked(updatedLists.some((list) => list.contains_item));
			setIsKnown(updatedLists.some((list) => list.contains_item && list.type === isKnownType));
			setLists(updatedLists);
		} catch (error) {
			console.error(error);
		} finally {
			setIsLoadingUserCatalogues(false);
		}
	};

	return (
		<div>
			<div className="col-md-4">
				{!isLoadingUserCatalogues &&
					(isKnown ? (
						<i className="fas fa-check-circle text-success"> Learned</i>
					) : (
						<i className="fas fa-check-circle text-secondary"> Not learned</i>
					))}
				<Button
					onClick={bookmarkModal.open}
					disabled={isLoadingUserCatalogues}
					className="btn btn-outline brand-button float-right"
					aria-controls={bookmarkModal.id}
					aria-expanded={bookmarkModal.isOpen}
				>
					<Icon size="md" name={isBookmarked ? 'bookmarkSolid' : 'bookmarkRegular'} />
				</Button>
			</div>

			<CatalogueBookmarkModal
				controller={bookmarkModal}
				lists={lists}
				loadingListIds={loadingListIds}
				onListAction={addToOrRemoveFromList}
				title={modalTitle}
				ariaLabel={modalTitle}
			/>
		</div>
	);
};
