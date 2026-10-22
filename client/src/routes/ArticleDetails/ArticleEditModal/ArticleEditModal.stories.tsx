import { useRef, type ComponentProps } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { mapArticleDetail } from '@/api/articles/details';
import { getArticleUpdateMockHandler, getArticleUpdateResponseMock } from '@/api/generated/article/article.msw';
import type { UpdateArticleRequest } from '@/api/generated/model/updateArticleRequest';
import { Button } from '@/components/shared/Button';
import { useModal } from '@/hooks/useModal';
import { articleDetail, failWith, rejectWith } from '../../routeFixtures';
import ArticleEditModal from './';

type ModalProps = ComponentProps<typeof ArticleEditModal>;

/** Owns the dialog the way the article page does: its ref and open state come from `useModal`. */
const WithTrigger = ({ article, onClose }: Pick<ModalProps, 'article'> & { onClose?: () => void }) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef, { onClose });

	return (
		<>
			<Button onClick={controller.open}>Edit article</Button>
			<ArticleEditModal article={article} controller={controller} />
		</>
	);
};

const openDialog = async (canvasElement: HTMLElement) => {
	await userEvent.click(within(canvasElement).getByRole('button', { name: 'Edit article' }));

	const dialog = within(await within(document.body).findByRole('dialog', { name: 'Edit Article' }));
	// The panel fades in; wait until it is fully there before reading or clicking it.
	await waitFor(() => expect(dialog.getByRole('heading', { name: 'Edit Article' })).toBeVisible());

	return dialog;
};

const meta = {
	title: 'Features/Articles/ArticleEditModal',
	component: ArticleEditModal,
	parameters: { layout: 'centered', auth: 'member' },
	// The render below owns the controller; this placeholder only satisfies the props type.
	args: {
		article: mapArticleDetail(articleDetail()),
		controller: {
			id: 'storybook-article-edit',
			dialogRef: { current: null },
			isOpen: false,
			isRendered: false,
			open: fn(),
			close: fn(),
		},
	},
	argTypes: { controller: { control: false } },
	render: ({ article }) => <WithTrigger article={article} />,
} satisfies Meta<typeof ArticleEditModal>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated PUT handler received, newest last; cleared before each story that saves. */
const saved: UpdateArticleRequest[] = [];

const editEnglishTitleAndSave = async (dialog: ReturnType<typeof within>) => {
	await userEvent.type(dialog.getByLabelText('English title'), ' today');
	await userEvent.click(dialog.getByRole('button', { name: 'Save changes' }));
};

/** Open the modal, change the English title and save: only that field is sent, and the modal closes. */
export const Default: Story = {
	beforeEach: ({ msw }) => {
		saved.length = 0;

		msw.use(
			getArticleUpdateMockHandler(async ({ request }) => {
				const body = (await request.json()) as UpdateArticleRequest;
				saved.push(body);

				return getArticleUpdateResponseMock({ title_en: body.title_en ?? undefined });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getByLabelText('Japanese title')).toHaveValue('新宿駅の新しい改札が完成');

		await userEvent.click(dialog.getByRole('button', { name: 'Save changes' }));
		await expect(await dialog.findByRole('status')).toHaveTextContent('No changes to save.');
		await expect(saved).toEqual([]);

		await editEnglishTitleAndSave(dialog);

		await waitFor(() => expect(within(document.body).queryByRole('dialog')).toBeNull());
		await expect(saved).toEqual([{ title_en: 'New ticket gates completed at Shinjuku Station today' }]);
	},
};

/** The PUT never answers: the save control is disabled while it is in flight, and the modal stays open. */
export const Saving: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getArticleUpdateMockHandler(async () => {
				await delay('infinite');

				return getArticleUpdateResponseMock();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await editEnglishTitleAndSave(dialog);

		await waitFor(() => expect(dialog.getByRole('button', { name: 'Save changes' })).toBeDisabled());
		await expect(within(document.body).getByRole('dialog', { name: 'Edit Article' })).toBeVisible();
	},
};

/** A 500 shows the general error inside the modal and keeps the edit, so the author can retry. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getArticleUpdateMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await editEnglishTitleAndSave(dialog);

		await expect(await dialog.findByRole('alert')).toHaveTextContent('Something went wrong. Please try again.');
		await expect(dialog.getByLabelText('English title')).toHaveValue(
			'New ticket gates completed at Shinjuku Station today',
		);
		await expect(dialog.getByRole('button', { name: 'Save changes' })).toBeEnabled();
	},
};

/** A 422 puts the message under its field inside the modal. */
export const ServerValidation: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getArticleUpdateMockHandler(() => rejectWith({ title_en: ['The title en has already been taken.'] })));
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await editEnglishTitleAndSave(dialog);

		await expect(await dialog.findByRole('alert')).toHaveTextContent('Check the highlighted fields and try again.');
		await expect(dialog.getByLabelText('English title')).toHaveAccessibleDescription(/already been taken/);
	},
};
