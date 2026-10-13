import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, waitFor } from 'storybook/test';
import { currentLocation } from '@/storybook/decorators';
import { ARTICLE_UUID } from '../routeFixtures';
import ArticleEditRedirect from './';

/*
 * `/article/edit/:article_id` no longer has a page of its own: it replaces itself with the article
 * detail in edit mode (`?edit=1`), where the edit modal opens. So there is no entity to load and
 * no loading, not-found or not-owner state here; those belong to `Pages/ArticleDetails/Route states`.
 */

const meta = {
	title: 'Pages/ArticleEdit',
	component: ArticleEditRedirect,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/article/edit/:article_id', initialEntries: [`/article/edit/${ARTICLE_UUID}`] },
	},
} satisfies Meta<typeof ArticleEditRedirect>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The old edit address lands on the article with the edit modal flag, in place of the history entry. */
export const RedirectsToDetail: Story = {
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe(`/articles/${ARTICLE_UUID}?edit=1`));
	},
};
