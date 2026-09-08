import { CatalogueArticleItem } from '@/api/catalogues/catalogues';
import { ObjectTemplates } from '@/shared/constants';
import type { User } from '@/types';
import CatalogueArticleItems from './CatalogueArticleItems';
import CatalogueKanjiItems from './CatalogueKanjiItems';
import CatalogueRadicalItems from './CatalogueRadicalItems';
import CatalogueSentenceItems from './CatalogueSentenceItems';
import CatalogueWordItems from './CatalogueWordItems';

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
			// TODO: add a typed catalogue radical-item boundary type when these items move off the generic path.
			return (
				<CatalogueRadicalItems
					editMode={editMode}
					ownerId={ownerId}
					currentUser={compatibleCurrentUser}
					items={compatibleItems}
					onRemoveItem={handleRemoveItem}
				/>
			);
		case ObjectTemplates.KNOWNKANJIS:
		case ObjectTemplates.KANJIS:
			// TODO: add a typed catalogue kanji-item boundary type when these items move off the generic path.
			return (
				<CatalogueKanjiItems
					editMode={editMode}
					ownerId={ownerId}
					currentUser={compatibleCurrentUser}
					items={compatibleItems}
					onRemoveItem={handleRemoveItem}
				/>
			);
		case ObjectTemplates.KNOWNWORDS:
		case ObjectTemplates.WORDS:
			// TODO: add a typed catalogue word-item boundary type when these items move off the generic path.
			return (
				<CatalogueWordItems
					editMode={editMode}
					ownerId={ownerId}
					currentUser={compatibleCurrentUser}
					items={compatibleItems}
					onRemoveItem={handleRemoveItem}
				/>
			);
		case ObjectTemplates.KNOWNSENTENCES:
		case ObjectTemplates.SENTENCES:
			// TODO: add a typed catalogue sentence-item boundary type when these items move off the generic path.
			return (
				<CatalogueSentenceItems
					editMode={editMode}
					ownerId={ownerId}
					currentUser={compatibleCurrentUser}
					items={compatibleItems}
					onRemoveItem={handleRemoveItem}
				/>
			);
		case ObjectTemplates.ARTICLES:
			return (
				<CatalogueArticleItems
					editMode={editMode}
					ownerId={ownerId}
					currentUser={compatibleCurrentUser}
					items={items as CatalogueArticleItem[]}
					onRemoveItem={handleRemoveItem}
				/>
			);
		default:
			return <p>Unknown catalogue type</p>;
	}
};
