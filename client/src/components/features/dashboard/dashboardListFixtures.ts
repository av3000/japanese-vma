import type { CatalogueResource } from '@/api/generated/model';
import { fixtureOwner, fixtureUuid, UNBROKEN_TITLE } from './dashboardFixtures';
import { PUBLICITY } from './dashboardValues';

/*
 * Dashboard list rows shaped like the real owner payload
 * (`GET /catalogues?owner_uid=…&custom_only=false`): the built-in Known lists a new user starts
 * with, and the hostile cases from UI-DASH-00 (#450).
 */

let nextCatalogueId = 1;

export const makeDashboardCatalogue = (overrides: Partial<CatalogueResource> = {}): CatalogueResource => {
	const id = nextCatalogueId++;

	return {
		id,
		uuid: fixtureUuid('c0000000', id),
		type: 6,
		type_label: 'Kanji',
		title: 'N3 kanji for news reading',
		description: null,
		publicity: PUBLICITY.PUBLIC,
		owner: fixtureOwner,
		items_count: 42,
		hashtags: [],
		engagement: { likes_count: '12', views_count: '1532', downloads_count: '3', comments_count: '2' },
		jlpt_levels: null,
		created_at: '2026-09-01T09:00:00Z',
		updated_at: '2026-09-28T09:00:00Z',
		...overrides,
	};
};

/** A brand-new user's Lists tab: the four built-in Known lists, empty and private. */
export const knownLists: CatalogueResource[] = (
	[
		[1, 'Known Radicals', 'Known Radicals'],
		[2, 'Known Kanji', 'Known Kanjis'],
		[3, 'Known Words', 'Known Words'],
		[4, 'Known Sentences', 'Known Sentences'],
	] as const
).map(([type, typeLabel, title]) =>
	makeDashboardCatalogue({
		type,
		type_label: typeLabel,
		title,
		publicity: PUBLICITY.PRIVATE,
		items_count: 0,
		engagement: null,
		created_at: '2026-01-01T09:00:00Z',
		updated_at: '2026-01-01T09:00:00Z',
	}),
);

export const hostileCatalogues: CatalogueResource[] = [
	makeDashboardCatalogue({ title: UNBROKEN_TITLE }),
	makeDashboardCatalogue({ type: 9, type_label: 'Articles', title: '空のリスト', items_count: 0, engagement: null }),
	makeDashboardCatalogue({ type: 7, type_label: 'Words', title: 'Private verbs', publicity: PUBLICITY.PRIVATE }),
	makeDashboardCatalogue({ type: 8, type_label: 'Sentences', title: 'Daily phrases', items_count: 13108 }),
	makeDashboardCatalogue({ type: 5, type_label: 'Radicals', title: 'Radicals to review' }),
];

export const manyCatalogues = (count: number): CatalogueResource[] =>
	Array.from({ length: count }, (_, index) =>
		makeDashboardCatalogue({
			id: 20_000 + index,
			uuid: fixtureUuid('c0000002', index),
			title: `List ${index + 1}`,
		}),
	);
