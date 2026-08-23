import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useInfiniteRadicals } from '@/api/radicals/hooks/useInfiniteRadicals';
import RadicalsList from './index';

const setSearchParamsMock = vi.fn();
let searchParams = new URLSearchParams();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useSearchParams: () => [searchParams, setSearchParamsMock],
	};
});
vi.mock('@/api/radicals/hooks/useInfiniteRadicals', async () => {
	const actual = await vi.importActual<typeof import('@/api/radicals/hooks/useInfiniteRadicals')>(
		'@/api/radicals/hooks/useInfiniteRadicals',
	);
	return { ...actual, useInfiniteRadicals: vi.fn() };
});
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

const useInfiniteRadicalsMock = vi.mocked(useInfiniteRadicals);

describe('RadicalsList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		searchParams = new URLSearchParams();
		useInfiniteRadicalsMock.mockReturnValue({
			radicals: [{ id: 9, uuid: 'radical-uuid', radical: '水', strokes: 4, meaning: 'water', hiragana: 'みず' }],
			total: 1,
			isLoading: false,
			isFetchingNextPage: false,
			hasNextPage: false,
			fetchNextPage: vi.fn(),
			isError: false,
		} as unknown as ReturnType<typeof useInfiniteRadicals>);
	});

	it('derives the URL keyword and uses the UUID detail link', () => {
		searchParams = new URLSearchParams('keyword=water');
		const html = renderToStaticMarkup(<RadicalsList />);

		expect(useInfiniteRadicalsMock).toHaveBeenCalledWith({ filters: { keyword: 'water', per_page: 10 } });
		expect(html).toContain('/radical/radical-uuid');
	});
});
