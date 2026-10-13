import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getCatalogueIndexMockHandler } from '@/api/generated/catalogue/catalogue.msw';
import { libraryCatalogues, makeCatalogue, popularCatalogues } from '@/components/features/Homepage/fixtures';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { createRequestLog, pageOf } from '../pageFixtures';
import CataloguesList, { DEFAULT_PER_PAGE } from './';
import CataloguesListSkeleton from './CatalogueListSkeleton/CataloguesListSkeleton';

/*
 * `/catalogues` on its real route component and hooks: the URL owns the keyword, type and sort,
 * and the index request goes through the generated MSW handler. The list pages by Load more (an
 * infinite query), so there is no `page` in the URL; the paged story asks for the second page.
 */

const TOTAL = 30;

/** The hostile catalogues first, then plain numbered ones, so a page always has unique uuids. */
const rowAt = (position: number) => {
	const hostile = [...Object.values(libraryCatalogues), ...popularCatalogues][position - 1];

	return makeCatalogue({
		...hostile,
		id: position,
		uuid: `catalogue-${position}`,
		title: hostile?.title ?? `Catalogue ${position}`,
	});
};

const requests = createRequestLog();

/** Answers every index request with `total` rows, and records its query string. */
const cataloguesIndex = (total = TOTAL) =>
	getCatalogueIndexMockHandler(({ request }) => {
		requests.record(request);

		return pageOf(request, total, rowAt, DEFAULT_PER_PAGE);
	});

const searchOf = (location: string) => new URLSearchParams(location.split('?')[1]);

const meta = {
	title: 'Pages/CataloguesList/Page',
	component: CataloguesList,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/catalogues', initialEntries: ['/catalogues'] },
	},
	beforeEach: ({ msw }) => {
		requests.clear();
		msw.use(cataloguesIndex());
	},
} satisfies Meta<typeof CataloguesList>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The first page of 30: twelve cards (hostile data first) and Load More. Guests get no "New catalogue". */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 12 of 30')).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 1, name: 'Catalogues' })).toBeVisible();
		await expect(canvas.getAllByRole('article')).toHaveLength(12);
		await expect(canvas.getByRole('button', { name: 'Load More' })).toBeVisible();
		await expect(canvas.queryByRole('link', { name: 'New catalogue' })).not.toBeInTheDocument();

		await expect(requests.searches).toHaveLength(1);
		await expect(requests.last()).toMatchObject({
			page: '1',
			per_page: '12',
			sort_by: 'created_at',
			sort_dir: 'desc',
			public_only: 'true',
			custom_only: 'true',
		});
		await expect(requests.last().type).toBeUndefined();
		await expect(requests.last().search).toBeUndefined();
	},
};

/** The placeholder the route shows while its chunk loads: filter controls and twelve card shapes. */
export const Skeleton: Story = {
	render: () => <CataloguesListSkeleton />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByTestId('catalogues-list-skeleton')).toHaveAttribute('aria-hidden', 'true');
		await expect(canvas.getAllByTestId('catalogue-card-skeleton')).toHaveLength(12);
		await expect(requests.searches).toHaveLength(0);
	},
};

export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('Showing 12 of 30')).toBeVisible();
		await expect(within(canvasElement).queryByRole('link', { name: 'New catalogue' })).not.toBeInTheDocument();
	},
};

/** A signed-in viewer gets "New catalogue" beside the title. */
export const Member: Story = {
	parameters: { auth: { user: users.member } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('link', { name: 'New catalogue' })).toHaveAttribute(
			'href',
			'/catalogues/new',
		);
	},
};

/** A shared link: keyword, type and sort in the URL reach the request and the controls. */
export const Filtered: Story = {
	parameters: {
		router: {
			path: '/catalogues',
			initialEntries: ['/catalogues?q=%E6%BC%A2%E5%AD%97&type=6&sort=pop'],
		},
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 12 of 30 · Results for: 漢字')).toBeVisible();
		await expect(canvas.getByRole('searchbox', { name: 'Search catalogues' })).toHaveValue('漢字');
		await expect(canvas.getByRole('combobox', { name: 'Catalogue type' })).toHaveValue('6');
		await expect(canvas.getByRole('combobox', { name: 'Sort by' })).toHaveValue('pop');

		await expect(requests.last()).toMatchObject({ search: '漢字', type: '6', sort_by: 'views' });
	},
};

/**
 * A type or sort change applies at once; the keyword waits for Enter. Each rewrites the URL and
 * sends a new request carrying it.
 */
export const FilterChangesRequest: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 12 of 30');

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'Catalogue type' }), 'Words');
		await waitFor(() => expect(requests.last().type).toBe('7'));
		await expect(searchOf(currentLocation()).get('type')).toBe('7');

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'Sort by' }), 'Popular');
		await waitFor(() => expect(requests.last().sort_by).toBe('views'));
		await expect(searchOf(currentLocation()).get('sort')).toBe('pop');

		const requestsBeforeTyping = requests.searches.length;
		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search catalogues' }), 'cooking');
		await expect(requests.searches).toHaveLength(requestsBeforeTyping);

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search catalogues' }), '{Enter}');
		await waitFor(() => expect(requests.last().search).toBe('cooking'));
		await expect(requests.last()).toMatchObject({ type: '7', sort_by: 'views' });
		await expect(searchOf(currentLocation()).get('q')).toBe('cooking');
	},
};

/** Load More asks for page 2 with the same filters, and the cards append: 24 of 30 shown. */
export const Paged: Story = {
	parameters: { router: { path: '/catalogues', initialEntries: ['/catalogues?type=6'] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 12 of 30');

		await userEvent.click(canvas.getByRole('button', { name: 'Load More' }));

		await expect(await canvas.findByText('Showing 24 of 30')).toBeVisible();
		await expect(canvas.getAllByRole('article')).toHaveLength(24);
		await expect(requests.searches).toHaveLength(2);
		await expect(requests.last()).toMatchObject({ page: '2', per_page: '12', type: '6' });

		await userEvent.click(canvas.getByRole('button', { name: 'Load More' }));
		await expect(await canvas.findByText('Showing 30 of 30')).toBeVisible();
		await expect(await canvas.findByText('No more results')).toBeVisible();
		await expect(requests.last().page).toBe('3');
	},
};

/** No public catalogues at all: the empty state, and no "No more results" line under it. */
export const Empty: Story = {
	beforeEach: ({ msw }) => {
		msw.use(cataloguesIndex(0));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('No public catalogues yet')).toBeVisible();
		await expect(canvas.queryByText('No more results')).not.toBeInTheDocument();
		await expect(canvas.queryByRole('article')).not.toBeInTheDocument();
	},
};

/** A search that matches nothing names the term. */
export const NoResults: Story = {
	parameters: {
		router: { path: '/catalogues', initialEntries: ['/catalogues?q=%E3%81%B5%E3%81%97%E3%81%8E%E3%81%AA'] },
	},
	beforeEach: ({ msw }) => {
		msw.use(cataloguesIndex(0));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('heading', { level: 2, name: /No catalogues match/ })).toBeVisible();
		await expect(canvas.getByText('ふしぎな')).toBeVisible();
		await expect(requests.last().search).toBe('ふしぎな');
	},
};

/** The index request never answers: the header and the list skeleton, announced as loading. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueIndexMockHandler(async ({ request }) => {
				await delay('infinite');

				return pageOf(request, 0, rowAt, DEFAULT_PER_PAGE);
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('status')).toHaveTextContent('Loading page.');
		await expect(canvas.getByRole('heading', { level: 1, name: 'Catalogues' })).toBeVisible();
		await expect(canvas.getByTestId('catalogues-list-skeleton')).toBeInTheDocument();
	},
};

/** The index request fails with a 500: the header stays and an alert replaces the list. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueIndexMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText(/^Error:/)).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 1, name: 'Catalogues' })).toBeVisible();
		await expect(canvas.queryByRole('article')).not.toBeInTheDocument();
	},
};

/** One column of cards, and the filter bar stacked, at 360px. */
export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('Showing 12 of 30')).toBeVisible();
	},
};
