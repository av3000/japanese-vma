import * as React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { CatalogueBookmarkListItem } from '@/api/catalogues/bookmarkMembership';
import WordDetails from './index';

const updateElementCatalogueMembershipMock = vi.fn();
const updateCatalogueMembershipLegacyMock = vi.fn();
const useNavigateMock = vi.fn();
const bootstrapButtonProps: Array<{ onClick?: () => Promise<void> | void; variant?: string }> = [];

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
		Link: ({ children, to }: { children: React.ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => useNavigateMock,
		useParams: () => ({ word_id: '42' }),
	};
});

vi.mock('react-bootstrap', () => ({
	Button: ({
		children,
		onClick,
		variant,
	}: {
		children: React.ReactNode;
		onClick?: () => Promise<void> | void;
		variant?: string;
	}) => {
		bootstrapButtonProps.push({ onClick, variant });
		return <button type="button">{children}</button>;
	},
	Modal: Object.assign(
		({ children }: { children: React.ReactNode }) => <div>{children}</div>,
		{
			Header: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
			Title: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
			Body: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
			Footer: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
		},
	),
}));

vi.mock('@/api/catalogues/bookmarkMembership', async () => {
	const actual = await vi.importActual<typeof import('@/api/catalogues/bookmarkMembership')>(
		'@/api/catalogues/bookmarkMembership',
	);
	return {
		...actual,
		updateElementCatalogueMembership: (...args: unknown[]) => updateElementCatalogueMembershipMock(...args),
	};
});

vi.mock('@/api/catalogues/actions', () => ({
	updateCatalogueMembership: (...args: unknown[]) => updateCatalogueMembershipLegacyMock(...args),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		isAuthenticated: true,
	}),
}));

vi.mock('@/services/api', () => ({
	apiCall: vi.fn(),
}));

vi.mock('@/components/shared/Chip', () => ({
	Chip: ({ children }: { children: React.ReactNode }) => <span>{children}</span>,
}));

vi.mock('@/assets/images/spinner.gif', () => ({
	default: 'spinner.gif',
}));

describe('WordDetails', () => {
	const list: CatalogueBookmarkListItem = {
		id: 7,
		uuid: 'd453be67-1519-43e2-94ab-af85b79aeb31',
		title: 'Known words',
		type: 3,
		elementBelongsToList: false,
	};

	beforeEach(() => {
		vi.clearAllMocks();
		bootstrapButtonProps.length = 0;
		updateElementCatalogueMembershipMock.mockResolvedValue(undefined);
		updateCatalogueMembershipLegacyMock.mockResolvedValue(undefined);
		vi.mocked(React.useState).mockReset();
	});

	it('uses the shared v1 membership write helper with a numeric word id and updates known-word state by list id', async () => {
		const setWordMock = vi.fn();
		const setKanjisMock = vi.fn();
		const setArticlesMock = vi.fn();
		const setShowModalMock = vi.fn();
		const setIsLoadingMock = vi.fn();
		const setLoadingListIdsMock = vi.fn();
		const setWordIsKnownMock = vi.fn();
		let updatedLists: CatalogueBookmarkListItem[] | undefined;

		const setListsMock = vi.fn((updater: CatalogueBookmarkListItem[] | ((prev: CatalogueBookmarkListItem[]) => CatalogueBookmarkListItem[])) => {
			if (typeof updater === 'function') {
				updatedLists = updater([list]);
			}
		});

		vi.mocked(React.useState)
			.mockImplementationOnce(() => [{} as never, setWordMock])
			.mockImplementationOnce(() => [[] as never, setKanjisMock])
			.mockImplementationOnce(() => [[] as never, setArticlesMock])
			.mockImplementationOnce(() => [[list] as never, setListsMock as never])
			.mockImplementationOnce(() => [true as never, setShowModalMock])
			.mockImplementationOnce(() => [false as never, setWordIsKnownMock])
			.mockImplementationOnce(() => [false as never, setIsLoadingMock])
			.mockImplementationOnce(() => [[] as never, setLoadingListIdsMock]);

		renderToStaticMarkup(<WordDetails />);

		const addButton = bootstrapButtonProps.find((props) => props.variant === 'primary');
		await addButton?.onClick?.();

		expect(updateElementCatalogueMembershipMock).toHaveBeenCalledWith({
			list,
			elementId: 42,
			action: 'add',
		});
		expect(updateCatalogueMembershipLegacyMock).not.toHaveBeenCalled();
		expect(updatedLists).toEqual([{ ...list, elementBelongsToList: true }]);
		expect(setWordIsKnownMock).toHaveBeenCalledWith(true);
	});
});
