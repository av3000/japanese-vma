import type { User } from '@/types';
import { ObjectTemplates } from '@/shared/constants';
import ListArticlesList from '@/components/features/SavedList/SavedLists/SavedArticlesList';
import ListKanjisList from '@/components/features/SavedList/SavedLists/SavedKanjisList';
import ListRadicalList from '@/components/features/SavedList/SavedLists/SavedRadicalList';
import ListSentencesList from '@/components/features/SavedList/SavedLists/SavedSentencesList';
import ListWordsList from '@/components/features/SavedList/SavedLists/SavedWordsList';

interface CatalogueItemsProps {
	// TODO: replace `unknown[]` with a backend/Orval-generated catalogue item union once
	// the catalogue detail schema exposes typed item payloads per catalogue type.
	items: unknown[];
	catalogueType: number;
	currentUser: User | null;
	ownerId: number;
	editMode: boolean;
	onRemoveItem: (id: number) => void;
}

export const CatalogueItems = ({
	items,
	catalogueType,
	currentUser,
	ownerId,
	editMode,
	onRemoveItem,
}: CatalogueItemsProps) => {
	const compatibleCurrentUser = currentUser as User;
	const compatibleItems = items as any;
	const handleRemoveItem = (id: string | number) => {
		onRemoveItem(Number(id));
	};

	switch (catalogueType) {
		case ObjectTemplates.KNOWNRADICALS:
		case ObjectTemplates.RADICALS:
			return (
				<ListRadicalList
					editToggle={editMode}
					listUserId={ownerId}
					currentUser={compatibleCurrentUser}
					objects={compatibleItems}
					removeFromList={handleRemoveItem}
				/>
			);
		case ObjectTemplates.KNOWNKANJIS:
		case ObjectTemplates.KANJIS:
			return (
				<ListKanjisList
					editToggle={editMode}
					listUserId={ownerId}
					currentUser={compatibleCurrentUser}
					objects={compatibleItems}
					removeFromList={handleRemoveItem}
				/>
			);
		case ObjectTemplates.KNOWNWORDS:
		case ObjectTemplates.WORDS:
			return (
				<ListWordsList
					editToggle={editMode}
					listUserId={ownerId}
					currentUser={compatibleCurrentUser}
					objects={compatibleItems}
					removeFromList={handleRemoveItem}
				/>
			);
		case ObjectTemplates.KNOWNSENTENCES:
		case ObjectTemplates.SENTENCES:
			return (
				<ListSentencesList
					editToggle={editMode}
					listUserId={ownerId}
					currentUser={compatibleCurrentUser}
					objects={compatibleItems}
					removeFromList={handleRemoveItem}
				/>
			);
		case ObjectTemplates.ARTICLES:
			return (
				<ListArticlesList
					listUserId={ownerId}
					currentUser={compatibleCurrentUser}
					objects={compatibleItems}
					removeFromList={handleRemoveItem}
				/>
			);
		default:
			return <p>Unknown catalogue type</p>;
	}
};
