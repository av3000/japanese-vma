import { useCallback, useEffect, useRef, useState } from 'react';
import {
	deriveCatalogueWidgetState,
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
import styles from './AuthorizedBookmarkWidget.module.scss';

// TODO: For lists it shouldnt fetch per instance, need to figure cheaper way to get it on list get request.
interface AuthorizedBookmarkWidgetProps {
	entityId: number; // TODO: Might consider uuid, but maybe it doesnt make a difference.
	instanceObjectType: SavedListType;
	isKnownType?: SavedListType;
	modalTitle?: string;
	initialIsBookmarked?: boolean;
	initialIsKnown?: boolean;
	loadOnMount?: boolean;
	onStateChange?: (state: { isBookmarked: boolean; isKnown: boolean }) => void;
}

export const AuthorizedBookmarkWidget: React.FC<AuthorizedBookmarkWidgetProps> = ({
	entityId,
	instanceObjectType,
	isKnownType,
	modalTitle = 'Choose Instance List to add',
	initialIsBookmarked = false,
	initialIsKnown = false,
	loadOnMount = true,
	onStateChange,
}) => {
	const [lists, setLists] = useState<CatalogueForItem[]>([]);
	const [isBookmarked, setIsBookmarked] = useState(initialIsBookmarked);
	const [isKnown, setIsKnown] = useState(initialIsKnown);
	const [hasLoadedUserCatalogues, setHasLoadedUserCatalogues] = useState(false);
	const [isLoadingUserCatalogues, setIsLoadingUserCatalogues] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState<number[]>([]);
	const bookmarkDialogRef = useRef<HTMLDialogElement | null>(null);
	const bookmarkModal = useModal(bookmarkDialogRef, { id: 'sentence-bookmark-modal' });
	const isInvalidEntity = !entityId || Number.isNaN(entityId);

	useEffect(() => {
		setIsBookmarked(initialIsBookmarked);
		setIsKnown(initialIsKnown);
	}, [initialIsBookmarked, initialIsKnown]);

	const loadUserCatalogues = useCallback(async () => {
		if (isInvalidEntity) {
			setLists([]);
			setIsBookmarked(false);
			setIsKnown(false);
			setHasLoadedUserCatalogues(true);
			return [];
		}

		setIsLoadingUserCatalogues(true);

		try {
			const instanceTypes = [instanceObjectType];
			if (isKnownType) {
				instanceTypes.push(isKnownType);
			}

			const updatedLists = await fetchCataloguesForItem(entityId, {
				types: instanceTypes,
			});
			const nextState = deriveCatalogueWidgetState(updatedLists, instanceObjectType, isKnownType);

			setIsBookmarked(nextState.isBookmarked);
			setIsKnown(nextState.isKnown);
			setLists(updatedLists);
			setHasLoadedUserCatalogues(true);
			onStateChange?.(nextState);

			return updatedLists;
		} catch (error) {
			console.error(error);
			return [];
		} finally {
			setIsLoadingUserCatalogues(false);
		}
	}, [entityId, instanceObjectType, isInvalidEntity, isKnownType, onStateChange]);

	useEffect(() => {
		if (!loadOnMount) {
			return;
		}

		let isActive = true;

		const load = async () => {
			const updatedLists = await loadUserCatalogues();

			if (!isActive) {
				return;
			}

			setLists(updatedLists);
		};

		void load();

		return () => {
			isActive = false;
		};
	}, [loadOnMount, loadUserCatalogues]);

	const openBookmarkModal = async () => {
		bookmarkModal.open();

		if (!loadOnMount && !hasLoadedUserCatalogues && !isLoadingUserCatalogues) {
			await loadUserCatalogues();
		}
	};

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
				const nextState = deriveCatalogueWidgetState(updatedLists, instanceObjectType, isKnownType);

				setIsBookmarked(nextState.isBookmarked);
				setIsKnown(nextState.isKnown);
				onStateChange?.(nextState);

				return updatedLists;
			});
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== list.id));
		}
	};

	return (
		<>
			<div className={styles.widgetWrapper}>
				{isKnownType &&
					(isKnown ? (
						<i className="fas fa-check-circle text-success"> Learned</i>
					) : (
						<i className="fas fa-check-circle text-secondary"> Not learned</i>
					))}
				<Button
					onClick={openBookmarkModal}
					disabled={isInvalidEntity}
					variant="ghost"
					hasOnlyIcon
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
		</>
	);
};
