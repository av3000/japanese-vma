// @vitest-environment jsdom
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { choose, controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import CataloguesListPage from './index';

let capturedFilters: Record<string, unknown> | undefined;

vi.mock('@/api/catalogues/hooks/useInfiniteCatalogues', () => ({
	useInfiniteCatalogues: ({ filters }: { filters: Record<string, unknown> }) => {
		capturedFilters = filters;

		return {
			catalogues: [],
			total: 0,
			fetchNextPage: vi.fn(),
			hasNextPage: false,
			isFetchingNextPage: false,
			isPending: false,
			error: null,
			isError: false,
		};
	},
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated: false }),
}));

vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

let view: Awaited<ReturnType<typeof renderWithAct>>;

beforeEach(async () => {
	capturedFilters = undefined;
	view = await renderWithAct(
		<MemoryRouter>
			<CataloguesListPage />
		</MemoryRouter>,
	);
});

afterEach(async () => {
	await view.unmount();
});

const form = () => view.container.querySelector('form') as HTMLFormElement;

describe('CataloguesList filters', () => {
	it('starts with the same request as before: newest first, every type', () => {
		expect(capturedFilters).toMatchObject({
			search: undefined,
			sort_by: 'created_at',
			sort_dir: 'desc',
			type: undefined,
			public_only: true,
			custom_only: true,
		});
	});

	it('offers every catalogue type from the shared constant, with All as the empty choice', () => {
		const select = controlLabelled<HTMLSelectElement>(view.container, 'Catalogue type');
		const options = Array.from(select.options).map((option) => [option.value, option.textContent]);

		expect(options).toEqual([
			['', 'All'],
			['5', 'Radicals'],
			['6', 'Kanjis'],
			['7', 'Words'],
			['8', 'Sentences'],
			['9', 'Articles'],
		]);
	});

	it('requests nothing new until the form is submitted', async () => {
		await view.flush(() => {
			typeInto(controlLabelled(view.container, 'Search catalogues'), 'tokyo');
			choose(controlLabelled(view.container, 'Catalogue type'), '7');
			choose(controlLabelled(view.container, 'Sort by'), 'pop');
		});

		expect(capturedFilters).toMatchObject({ search: undefined, sort_by: 'created_at', type: undefined });
	});

	it('maps a keyword, a type and a sort onto the same request params as before on submit', async () => {
		await view.flush(() => {
			typeInto(controlLabelled(view.container, 'Search catalogues'), '  tokyo  ');
			choose(controlLabelled(view.container, 'Catalogue type'), '7');
			choose(controlLabelled(view.container, 'Sort by'), 'pop');
			submitForm(form());
		});

		expect(capturedFilters).toEqual({
			search: 'tokyo',
			sort_by: 'views',
			sort_dir: 'desc',
			type: 7,
			per_page: 12,
			public_only: true,
			custom_only: true,
			include_stats_counts: true,
			include_hashtags: true,
		});
	});

	it('sends no type when the filter goes back to All', async () => {
		await view.flush(() => {
			choose(controlLabelled(view.container, 'Catalogue type'), '9');
			submitForm(form());
		});
		expect(capturedFilters).toMatchObject({ type: 9 });

		await view.flush(() => {
			choose(controlLabelled(view.container, 'Catalogue type'), '');
			submitForm(form());
		});
		expect(capturedFilters).toMatchObject({ type: undefined });
	});
});
