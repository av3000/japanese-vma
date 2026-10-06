import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, waitFor, within } from '@storybook/test';
import { API_ERROR_MESSAGES, type ApiError } from '@/api/apiError';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { ArticleForm, type ArticleFormValues } from './ArticleForm';

type ArticleFormProps = React.ComponentProps<typeof ArticleForm>;

/** Answers the first submit with `apiError`, the way the route does after a rejected save. */
const RejectingServer = ({ apiError, onSubmit, ...props }: ArticleFormProps) => {
	const [current, setCurrent] = React.useState<ApiError | null>(null);

	return (
		<ArticleForm
			{...props}
			apiError={current}
			onSubmit={(values, meta) => {
				onSubmit(values, meta);
				setCurrent(apiError ?? null);
			}}
		/>
	);
};

const EMPTY: ArticleFormValues = {
	title_jp: '',
	title_en: '',
	content_jp: '',
	content_en: '',
	source_link: '',
	publicity: true,
	tags: [],
};

const SAVED: ArticleFormValues = {
	title_jp: '新宿駅の新しい改札が完成',
	title_en: 'New ticket gates completed at Shinjuku Station',
	content_jp:
		'ＪＲ新宿駅で、新しい改札が完成しました。東口と西口をつなぐ通路が広くなり、乗り換えがしやすくなります。工事は3年かかりました。',
	content_en: '',
	source_link: 'https://www3.nhk.or.jp/news/easy/k10014322571000/k10014322571000.html',
	publicity: true,
	tags: ['shinjuku', 'station'],
};

const LONGEST: ArticleFormValues = {
	title_jp: 'あ'.repeat(255),
	title_en: 'x'.repeat(255),
	content_jp: '新宿駅の新しい改札が完成しました。'.repeat(117).slice(0, 2000),
	content_en: 'The new ticket gates are finished. '.repeat(57).slice(0, 2000),
	source_link: `https://www3.nhk.or.jp/news/easy/${'k'.repeat(460)}.html`,
	publicity: false,
	tags: Array.from({ length: 10 }, (_, index) => `${index}${'タグ'.repeat(24)}t`.slice(0, 50)),
};

const EVERY_FIELD_ERROR: ApiError = {
	kind: 'validation',
	message: API_ERROR_MESSAGES.validation,
	errors: {
		title_jp: ['The title jp has already been taken.'],
		title_en: ['The title en may not be greater than 255 characters.'],
		content_jp: ['The content jp must be at least 10 characters.'],
		content_en: ['The content en must be at least 10 characters.'],
		publicity: ['The publicity field must be true or false.'],
		'tags.0': ['The tags.0 may not be greater than 50 characters.'],
		source_link: ['The source link must be a valid URL.'],
	},
};

const meta = {
	title: 'Features/Articles/ArticleForm',
	component: ArticleForm,
	parameters: { layout: 'fullscreen' },
	args: {
		initialValues: EMPTY,
		onSubmit: fn(),
		submitLabel: 'Create article',
		requireEnglishTitle: true,
		note: <p>We analyse the Japanese text for kanji and words, usually within a minute.</p>,
		cancel: (
			<Button variant="ghost" to="/articles">
				Cancel
			</Button>
		),
	},
	decorators: [
		(Story, { args }) => (
			<FormPage
				title={args.requireChanges ? 'Edit article' : 'New article'}
				backLink={{ to: '/articles', label: 'Articles' }}
			>
				<Story />
			</FormPage>
		),
	],
} satisfies Meta<typeof ArticleForm>;

export default meta;

type Story = StoryObj<typeof meta>;

/** An empty submit shows every client error and focuses the first invalid field. */
export const Create: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));

		await waitFor(() => expect(canvas.getByLabelText('Japanese title')).toHaveFocus());
		await expect(canvas.getByLabelText('Japanese title')).toHaveAccessibleDescription(
			'Japanese title is required.',
		);
		await expect(canvas.getByLabelText('Source link')).toHaveAttribute('aria-invalid', 'true');
		await expect(args.onSubmit).not.toHaveBeenCalled();
	},
};

export const Filled: Story = {
	args: { initialValues: SAVED },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));
		await waitFor(() => expect(args.onSubmit).toHaveBeenCalledTimes(1));
	},
};

/** Every server message sits under its own field; focus moves to the first one in form order. */
export const ServerErrorsOnEveryField: Story = {
	args: { initialValues: SAVED, apiError: EVERY_FIELD_ERROR },
	render: (args) => <RejectingServer {...args} />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));

		await expect(await canvas.findByRole('alert')).toHaveTextContent(API_ERROR_MESSAGES.validation);
		await waitFor(() => expect(canvas.getByLabelText('Japanese title')).toHaveFocus());
		await expect(canvas.getByLabelText('Japanese title')).toHaveAccessibleDescription(/already been taken/);
		await expect(canvas.getByLabelText('English title')).toHaveAccessibleDescription(/greater than 255/);
		await expect(canvas.getByLabelText('Japanese text')).toHaveAccessibleDescription(/at least 10/);
		await expect(canvas.getByLabelText('English translation (optional)')).toHaveAccessibleDescription(
			/at least 10/,
		);
		await expect(canvas.getByRole('group', { name: 'Visibility' })).toHaveAccessibleDescription(/true or false/);
		await expect(canvas.getByLabelText('Tags')).toHaveAccessibleDescription(/greater than 50/);
		await expect(canvas.getByLabelText('Source link')).toHaveAccessibleDescription(/valid URL/);
	},
};

/** A server error only in the Settings card still moves focus there. */
export const ServerErrorInSettings: Story = {
	args: {
		initialValues: SAVED,
		apiError: {
			kind: 'validation',
			message: API_ERROR_MESSAGES.validation,
			errors: { source_link: ['The source link must be a valid URL.'] },
		},
	},
	render: (args) => <RejectingServer {...args} />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));
		await waitFor(() => expect(canvas.getByLabelText('Source link')).toHaveFocus());
	},
};

/** An error that names no field shows only in the general alert. */
export const GeneralError: Story = {
	args: { initialValues: SAVED, apiError: { kind: 'unreachable', message: API_ERROR_MESSAGES.unreachable } },
	render: (args) => <RejectingServer {...args} />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));
		await expect(await canvas.findByRole('alert')).toHaveTextContent("We couldn't reach the server");
	},
};

/** The edit modal's form: one column, and an unchanged submit says so instead of sending. */
export const EditUnchanged: Story = {
	args: {
		initialValues: SAVED,
		submitLabel: 'Save changes',
		requireChanges: true,
		stacked: true,
		note: <p>Changing the Japanese title or text runs the kanji and word analysis again.</p>,
	},
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));

		await expect(await canvas.findByRole('status')).toHaveTextContent('No changes to save.');
		await expect(args.onSubmit).not.toHaveBeenCalled();

		await userEvent.type(canvas.getByLabelText('English title'), ' today');
		await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
		await waitFor(() => expect(args.onSubmit).toHaveBeenCalledTimes(1));
		await expect(args.onSubmit).toHaveBeenLastCalledWith(
			expect.objectContaining({ title_en: `${SAVED.title_en} today` }),
			{ dirtyKeys: ['title_en'] },
		);
	},
};

/** The longest value every field accepts. Nothing scrolls sideways. */
export const LongestText: Story = {
	args: { initialValues: LONGEST },
	play: async ({ canvasElement }) => {
		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};
