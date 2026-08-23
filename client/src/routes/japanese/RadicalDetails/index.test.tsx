import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import RadicalDetails from './index';

const useRadicalQueryMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useParams: () => ({ radical_id: 'radical-route-uuid' }),
	};
});
vi.mock('@/api/radicals/details', () => ({
	useRadicalQuery: (...args: unknown[]) => useRadicalQueryMock(...args),
}));
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated: true }) }));
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));
vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <div>Bookmark</div>;
	},
}));

describe('RadicalDetails', () => {
	beforeEach(() => {
		authorizedWidgetProps.length = 0;
		useRadicalQueryMock.mockReturnValue({
			data: {
				id: 88,
				uuid: 'radical-uuid',
				radical: '水',
				hiragana: 'みず',
				meaning: 'water',
				strokes: 4,
				kanjis: [{ uuid: 'kanji-uuid', character: '水', meanings: ['water'] }],
			},
			isLoading: false,
			isError: false,
		});
	});

	it('uses the route UUID and response ID', () => {
		const html = renderToStaticMarkup(<RadicalDetails />);

		expect(useRadicalQueryMock).toHaveBeenCalledWith('radical-route-uuid');
		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: 88,
			instanceObjectType: SavedListType.RADICALS,
			isKnownType: SavedListType.KNOWNRADICALS,
		});
		expect(html).toContain('/kanji/kanji-uuid');
	});
});
