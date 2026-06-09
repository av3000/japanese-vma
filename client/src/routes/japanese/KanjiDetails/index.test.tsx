import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import KanjiDetails from './index';

const authorizedWidgetProps: Array<{
	entityId: number;
	instanceObjectType: SavedListType;
	isKnownType: SavedListType;
	modalTitle?: string;
}> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useParams: () => ({ kanji_id: 'kanji-uuid' }),
	};
});

vi.mock('@/api/kanjis/details', () => ({
	useKanjiQuery: () => ({
		data: {
			id: 88,
			uuid: 'kanji-uuid',
			character: '水',
			onyomi: ['スイ'],
			kunyomi: ['みず'],
			meanings: ['water', 'river'],
			nanori: [],
			grade: '1',
			stroke_count: 4,
			jlpt: '5',
			frequency: 2,
			radicals: ['水'],
			radical_parts: ['水'],
			display: {
				meaning: 'water, river',
				onyomi: 'スイ',
				kunyomi: 'みず',
				nanori: '',
				radicalParts: '水',
				radicals: '水',
				grade: '1',
				jlpt: '5',
				frequency: '2',
			},
		},
		isLoading: false,
		isError: false,
	}),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		isAuthenticated: true,
	}),
}));

vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: {
		entityId: number;
		instanceObjectType: SavedListType;
		isKnownType: SavedListType;
		modalTitle?: string;
	}) => {
		authorizedWidgetProps.push(props);
		return <div>Authorized bookmark widget</div>;
	},
}));

vi.mock('@/assets/images/spinner.gif', () => ({
	default: 'spinner.gif',
}));

describe('KanjiDetails', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		authorizedWidgetProps.length = 0;
	});

	it('renders migrated v1 kanji detail data', () => {
		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toContain('水');
		expect(html).toContain('Meaning: water, river');
		expect(html).toContain('Onyomi: スイ');
		expect(html).toContain('Kunyomi: みず');
		expect(html).toContain('JLPT: 5');
	});

	it('uses numeric kanji id for catalogue actions', () => {
		renderToStaticMarkup(<KanjiDetails />);

		expect(authorizedWidgetProps[0]).toEqual({
			entityId: 88,
			instanceObjectType: SavedListType.KANJIS,
			isKnownType: SavedListType.KNOWNKANJIS,
			modalTitle: 'Choose Kanji List to add',
		});
	});

	it('does not render deferred related sections in the first slice', () => {
		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).not.toContain('Found in');
		expect(html).not.toContain('words');
		expect(html).not.toContain('sentences');
		expect(html).not.toContain('articles');
	});
});
