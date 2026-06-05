import * as React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import WordDetails from './index';

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
		Link: ({ children, to }: { children: React.ReactNode; to: string }) => <a href={to}>{children}</a>,
		useParams: () => ({ word_id: '42' }),
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

vi.mock('@/components/shared/Chip', () => ({
	Chip: ({ children }: { children: React.ReactNode }) => <span>{children}</span>,
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

describe('WordDetails', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		authorizedWidgetProps.length = 0;
	});

	it('uses the shared authorized bookmark widget for word catalogue actions', () => {
		renderToStaticMarkup(<WordDetails />);

		expect(authorizedWidgetProps[0]).toEqual({
			entityId: 42,
			instanceObjectType: SavedListType.WORDS,
			isKnownType: SavedListType.KNOWNWORDS,
			modalTitle: 'Choose Word List to add',
		});
	});
});
