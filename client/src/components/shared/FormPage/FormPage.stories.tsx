import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { FormField, Input, Textarea } from '@/components/shared/FormControls';
import { FormCard, FormLayout, FormNote, FormPage } from './';

const ContentFields = () => (
	<>
		<FormField label="Japanese title">
			{(control) => <Input {...control} defaultValue="新宿駅の新しい改札" />}
		</FormField>
		<FormField label="Japanese text" hint="The original text. No markup needed.">
			{(control) => <Textarea rows={6} {...control} />}
		</FormField>
	</>
);

const Actions = ({ label = 'Create article' }: { label?: string }) => (
	<>
		<Button type="submit" variant="primary">
			{label}
		</Button>
		<Button variant="ghost" to="/articles">
			Cancel
		</Button>
	</>
);

const meta = {
	title: 'Shared/FormPage',
	component: FormPage,
	tags: ['autodocs'],
	parameters: { layout: 'fullscreen' },
	args: {
		title: 'New article',
		backLink: { to: '/articles', label: 'Articles' },
		children: null,
	},
} satisfies Meta<typeof FormPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Content card with a Settings card beside it from 1024px, and below it under 1024px. */
export const TwoColumns: Story = {
	args: {
		children: (
			<form onSubmit={(event) => event.preventDefault()} noValidate>
				<FormLayout
					aside={
						<>
							<FormCard title="Settings">
								<FormField label="Source link">
									{(control) => <Input type="url" {...control} />}
								</FormField>
							</FormCard>
							<FormNote title="What happens next">
								<p>We analyse the Japanese text for kanji and words, usually within a minute.</p>
							</FormNote>
						</>
					}
					actions={<Actions />}
				>
					<FormCard>
						<ContentFields />
					</FormCard>
				</FormLayout>
			</form>
		),
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('region', { name: 'New article' })).toBeVisible();
		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getByRole('region', { name: 'Settings' })).toBeVisible();
		await expect(canvas.getByRole('complementary', { name: 'What happens next' })).toBeVisible();
	},
};

/** Single-card forms use the narrow page so the heading lines up with the card. */
export const SingleCard: Story = {
	args: {
		title: 'New sentence',
		size: 'sm',
		backLink: { to: '/sentences', label: 'Sentences' },
		children: (
			<form onSubmit={(event) => event.preventDefault()} noValidate>
				<FormLayout actions={<Actions label="Create sentence" />}>
					<FormCard>
						<FormField label="Sentence" hint="Between 4 and 300 characters.">
							{(control) => <Textarea rows={4} {...control} />}
						</FormField>
					</FormCard>
				</FormLayout>
			</form>
		),
	},
};

/** The general alert sits above the cards. */
export const WithAlert: Story = {
	args: {
		children: (
			<form onSubmit={(event) => event.preventDefault()} noValidate>
				<FormLayout
					alert={<Alert tone="danger">Check the highlighted fields and try again.</Alert>}
					aside={<FormCard title="Settings">Visibility and tags</FormCard>}
					actions={<Actions />}
				>
					<FormCard>
						<ContentFields />
					</FormCard>
				</FormLayout>
			</form>
		),
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('alert')).toHaveTextContent('Check the highlighted fields');
	},
};

/** One column at every width, as in the Article edit modal. */
export const Stacked: Story = {
	args: {
		title: 'Edit article',
		children: (
			<form onSubmit={(event) => event.preventDefault()} noValidate>
				<FormLayout
					stacked
					aside={<FormCard title="Settings">Visibility and tags</FormCard>}
					actions={<Actions label="Save changes" />}
				>
					<FormCard>
						<ContentFields />
					</FormCard>
				</FormLayout>
			</form>
		),
	},
};

/** A 255-character title with no spaces wraps instead of scrolling the page sideways. */
export const LongUnbrokenTitle: Story = {
	args: {
		title: 'あ'.repeat(120) + 'x'.repeat(135),
		intro: 'https://www3.nhk.or.jp/news/easy/' + 'k'.repeat(160),
		children: (
			<form onSubmit={(event) => event.preventDefault()} noValidate>
				<FormLayout aside={<FormCard title="Settings">{'#'.repeat(120)}</FormCard>} actions={<Actions />}>
					<FormCard>
						<ContentFields />
					</FormCard>
				</FormLayout>
			</form>
		),
	},
	play: async ({ canvasElement }) => {
		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};
