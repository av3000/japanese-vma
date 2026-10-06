import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Field, Input, Label } from '@/components/shared/FormControls';
import { Stack } from '@/components/shared/layout';
import { AuthCard } from './';

const LoginShapedForm = () => (
	<form onSubmit={(event) => event.preventDefault()}>
		<Stack gap="md">
			<Field>
				<Label htmlFor="story-email">Email</Label>
				<Input id="story-email" type="email" autoComplete="email" />
			</Field>
			<Field>
				<Label htmlFor="story-password">Password</Label>
				<Input id="story-password" type="password" autoComplete="current-password" />
			</Field>
			<Button type="submit" variant="primary">
				Log in
			</Button>
		</Stack>
	</form>
);

const meta = {
	title: 'Features/Auth/AuthCard',
	component: AuthCard,
	tags: ['autodocs'],
	parameters: { layout: 'fullscreen' },
	args: {
		title: 'Welcome back',
		children: <LoginShapedForm />,
		// A plain anchor keeps the story free of a router decorator.
		footer: (
			<>
				New here? <a href="/register">Create an account</a>
			</>
		),
	},
} satisfies Meta<typeof AuthCard>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('region', { name: 'Welcome back' })).toBeVisible();
		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getByRole('link', { name: 'Create an account' })).toHaveAttribute('href', '/register');
	},
};

/** The general error sits between the heading and the form. */
export const WithError: Story = {
	args: { alert: <Alert tone="danger">Email or password is incorrect.</Alert> },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('alert')).toHaveTextContent('Email or password is incorrect.');
	},
};

export const WithSessionExpired: Story = {
	args: { alert: <Alert tone="warning">Your session expired. Log in again to continue.</Alert> },
};

/** Long text wraps inside the card at every width. */
export const LongCopy: Story = {
	args: {
		title: 'Create your account to keep reading lists, bookmarks and progress in one place',
		intro: 'アカウントを作成すると、記事のブックマークや学習リストをすべての端末で同期できます。Your username is visible to other learners.',
	},
};

export const MobileViewport: Story = {
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};
