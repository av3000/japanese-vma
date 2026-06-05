import * as React from 'react';
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

vi.mock('react', async () => {
	const actual = await vi.importActual<typeof import('react')>('react');
	return {
		...actual,
		useState: vi.fn(actual.useState),
	};
});

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		useParams: () => ({ kanji_id: '88' }),
	};
});

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		isAuthenticated: true,
	}),
}));

vi.mock('@/services/api', () => ({
	apiCall: vi.fn(),
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({
		children,
		to,
		href,
	}: {
		children: React.ReactNode;
		to?: string;
		href?: string;
	}) =>
		to || href ? (
			<a href={to ?? href}>{children}</a>
		) : (
			<button type="button">{children}</button>
		),
}));

vi.mock('@/components/shared/Chip', () => ({
	Chip: ({ children }: { children: React.ReactNode }) => <span>{children}</span>,
}));

vi.mock('@/components/shared/Icon', () => ({
	Icon: ({ name }: { name: string }) => <span>{name}</span>,
}));

vi.mock('@/components/shared/Link', () => ({
	Link: ({ children, to }: { children: React.ReactNode; to: string }) => <a href={to}>{children}</a>,
}));

vi.mock('@/assets/images/spinner.gif', () => ({
	default: 'spinner.gif',
}));

vi.mock('../SentenceDetails/AuthorizedBookmarkWidget', () => ({
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

describe('KanjiDetails', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		authorizedWidgetProps.length = 0;
		vi.mocked(React.useState).mockReset();
	});

	it('uses the shared authorized bookmark widget for kanji catalogue actions', () => {
		const setKanjiMock = vi.fn();
		const setWordsMock = vi.fn();
		const setSentencesMock = vi.fn();
		const setArticlesMock = vi.fn();
		const setIsLoadingMock = vi.fn();

		vi.mocked(React.useState)
			.mockImplementationOnce(() => [
				{
					kanji: '火',
					hiragana: 'ひ',
					meaning: 'fire',
					onyomi: 'カ',
					kunyomi: 'ひ',
					radical_parts: '',
					stroke_count: 4,
					jlpt: 5,
					frequency: 100,
				} as never,
				setKanjiMock,
			])
			.mockImplementationOnce(() => [[] as never, setWordsMock])
			.mockImplementationOnce(() => [[] as never, setSentencesMock])
			.mockImplementationOnce(() => [[] as never, setArticlesMock])
			.mockImplementationOnce(() => [false as never, setIsLoadingMock]);

		renderToStaticMarkup(<KanjiDetails />);

		expect(authorizedWidgetProps[0]).toEqual({
			entityId: 88,
			instanceObjectType: SavedListType.KANJIS,
			isKnownType: SavedListType.KNOWNKANJIS,
			modalTitle: 'Choose Kanji List to add',
		});
	});
});
