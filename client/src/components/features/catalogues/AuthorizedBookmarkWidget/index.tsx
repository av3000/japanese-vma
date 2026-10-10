import { useCallback, useEffect, useId, useRef, useState } from 'react';
import classNames from 'classnames';
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
import styles from './AuthorizedBookmarkWidget.module.css';

// TODO: For lists it shouldnt fetch per instance, need to figure cheaper way to get it on list get request.
interface AuthorizedBookmarkWidgetProps {
	entityId: number; // TODO: Might consider uuid, but maybe it doesnt make a difference.
	instanceObjectType: SavedListType;
	isKnownType?: SavedListType;
	modalTitle?: string;
	/** What the page already knows about the viewer; shown until the viewer's lists have loaded. */
	initialIsBookmarked?: boolean;
	initialIsKnown?: boolean;
	loadOnMount?: boolean;
	/** Called once whenever the saved or known state changes, with the new state. */
	onStateChange?: (state: { isBookmarked: boolean; isKnown: boolean }) => void;
	/** Names the icon button, e.g. `水` gives "Save 水" or "Saved: 水". */
	itemLabel?: string;
	/** For table rows: no "Learned"/"Not learned" text, only a small "Known" mark when it applies. */
	compact?: boolean;
	/**
	 * For a detail page's rail: a full-width outline button with this visible text instead of the
	 * icon button, e.g. "Save to a catalogue". `savedLabel` replaces it once the item is saved.
	 */
	label?: string;
	savedLabel?: string;
}

const NO_LISTS: CatalogueForItem[] = [];

/*
 * State model: the viewer's catalogues for this item (`lists`) are the only state. Until they have
 * loaded (`null`), the saved and known flags come from the `initial*` props; afterwards they are
 * derived from `lists`. Once loaded, `lists` wins over later changes to the `initial*` props: it
 * was fetched after the page payload, and the tables that pass `onStateChange` write the same
 * values back into that payload anyway.
 */
export const AuthorizedBookmarkWidget: React.FC<AuthorizedBookmarkWidgetProps> = ({
	entityId,
	instanceObjectType,
	isKnownType,
	modalTitle = 'Choose a list to add this to',
	initialIsBookmarked = false,
	initialIsKnown = false,
	loadOnMount = true,
	onStateChange,
	itemLabel,
	compact = false,
	label,
	savedLabel,
}) => {
	const [lists, setLists] = useState<CatalogueForItem[] | null>(null);
	const [isLoadingUserCatalogues, setIsLoadingUserCatalogues] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState<number[]>([]);
	// The committed `lists`, readable from async handlers that started before the latest render.
	const listsRef = useRef<CatalogueForItem[] | null>(null);
	// The latest `onStateChange`, so an inline handler never changes what `loadLists` depends on.
	const onStateChangeRef = useRef(onStateChange);
	const bookmarkDialogRef = useRef<HTMLDialogElement | null>(null);
	// One dialog per widget: list rows render many widgets, so the id must be unique per instance.
	const dialogId = `bookmark-dialog-${useId()}`;
	const bookmarkModal = useModal(bookmarkDialogRef, { id: dialogId });
	const isInvalidEntity = !entityId || Number.isNaN(entityId);

	useEffect(() => {
		onStateChangeRef.current = onStateChange;
	});

	const { isBookmarked, isKnown } = isInvalidEntity
		? { isBookmarked: false, isKnown: false }
		: lists
			? deriveCatalogueWidgetState(lists, instanceObjectType, isKnownType)
			: { isBookmarked: initialIsBookmarked, isKnown: initialIsKnown };

	/** Stores the new lists and reports the state they imply, once. */
	const commitLists = useCallback(
		(nextLists: CatalogueForItem[]) => {
			listsRef.current = nextLists;
			setLists(nextLists);
			onStateChangeRef.current?.(deriveCatalogueWidgetState(nextLists, instanceObjectType, isKnownType));
		},
		[instanceObjectType, isKnownType],
	);

	/** `isCurrent` lets the mount effect drop a result it no longer wants (unmount, StrictMode replay). */
	const loadLists = useCallback(
		async (isCurrent: () => boolean = () => true) => {
			setIsLoadingUserCatalogues(true);

			try {
				const instanceTypes = [instanceObjectType];
				if (isKnownType) {
					instanceTypes.push(isKnownType);
				}

				const updatedLists = await fetchCataloguesForItem(entityId, { types: instanceTypes });

				if (isCurrent()) {
					commitLists(updatedLists);
				}
			} catch (error) {
				console.error(error);
			} finally {
				if (isCurrent()) {
					setIsLoadingUserCatalogues(false);
				}
			}
		},
		[commitLists, entityId, instanceObjectType, isKnownType],
	);

	useEffect(() => {
		if (!loadOnMount || isInvalidEntity) {
			return;
		}

		let isActive = true;

		void loadLists(() => isActive);

		return () => {
			isActive = false;
		};
	}, [isInvalidEntity, loadOnMount, loadLists]);

	const openBookmarkModal = async () => {
		bookmarkModal.open();

		if (!loadOnMount && lists === null && !isLoadingUserCatalogues) {
			await loadLists();
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
			commitLists(optimisticApplyCatalogueForItemAction(listsRef.current ?? NO_LISTS, list.id, action));
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== list.id));
		}
	};

	return (
		<>
			<div className={classNames(styles.widgetWrapper, compact && styles.compact, label && styles.labelled)}>
				{!compact &&
					isKnownType &&
					(isKnown ? (
						<i className={classNames('fas fa-check-circle', styles.learned)}> Learned</i>
					) : (
						<i className={classNames('fas fa-check-circle', styles.notLearned)}> Not learned</i>
					))}
				{label ? (
					<Button
						onClick={openBookmarkModal}
						disabled={isInvalidEntity}
						variant="outline"
						isFullWidth
						aria-controls={bookmarkModal.id}
						aria-expanded={bookmarkModal.isOpen}
					>
						<Icon size="sm" name={isBookmarked ? 'bookmarkSolid' : 'bookmarkRegular'} />
						{isBookmarked ? (savedLabel ?? label) : label}
					</Button>
				) : (
					<Button
						onClick={openBookmarkModal}
						disabled={isInvalidEntity}
						variant="ghost"
						hasOnlyIcon
						aria-controls={bookmarkModal.id}
						aria-expanded={bookmarkModal.isOpen}
						aria-label={itemLabel ? `${isBookmarked ? 'Saved: ' : 'Save '}${itemLabel}` : undefined}
					>
						<Icon size="md" name={isBookmarked ? 'bookmarkSolid' : 'bookmarkRegular'} />
					</Button>
				)}
				{compact && isKnownType && isKnown ? <span className={styles.knownMark}>Known</span> : null}
			</div>

			<CatalogueBookmarkModal
				controller={bookmarkModal}
				lists={lists ?? NO_LISTS}
				loadingListIds={loadingListIds}
				onListAction={addToOrRemoveFromList}
				title={modalTitle}
				ariaLabel={modalTitle}
			/>
		</>
	);
};
