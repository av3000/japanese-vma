import { useArgs } from '@storybook/preview-api';
import type { Meta, StoryObj } from '@storybook/react';
import { Popover, PopoverContent, PopoverDescription, PopoverHeader, PopoverTitle, PopoverTrigger } from './popover';

type PopoverStoryArgs = {
	open: boolean;
	modal: boolean;
	align: 'start' | 'center' | 'end';
	sideOffset: number;
};

const meta: Meta<PopoverStoryArgs> = {
	title: 'UI/Popover',
	tags: ['autodocs'],
	parameters: {
		docs: {
			story: { inline: false },
			canvas: { height: '500px' }, //TODO: check why doesnt apply for general popover page
			description: {
				component: `
**When to use**

- Use **Popover** for small contextual details/actions anchored to a trigger.
- Prefer **Modal/Dialog** for complex flows or long content.
`,
			},
		},
	},
	argTypes: {
		open: { control: 'boolean' },
		modal: { control: 'boolean' },
		align: {
			options: ['start', 'center', 'end'],
			control: { type: 'select' },
		},
		sideOffset: { control: { type: 'number', min: 0, max: 24, step: 1 } },
	},
	args: {
		open: false,
		modal: false,
		align: 'start',
		sideOffset: 8,
	},
};

export default meta;

type TriggerButtonProps = React.ComponentProps<'button'> & { children: React.ReactNode };

const TriggerButton = ({ children, ...props }: TriggerButtonProps) => (
	<button
		type="button"
		className="inline-flex items-center rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
		{...props}
	>
		{children}
	</button>
);

type Story = StoryObj<PopoverStoryArgs>;

export const Interactive: Story = {
	render: () => (
		<Popover>
			<PopoverTrigger asChild>
				<TriggerButton>Open popover</TriggerButton>
			</PopoverTrigger>
			<PopoverContent align="start">
				<PopoverHeader>
					<PopoverTitle>Popover</PopoverTitle>
					<PopoverDescription>Small contextual content anchored to a trigger.</PopoverDescription>
				</PopoverHeader>
				<div className="text-sm">This is the popover body.</div>
			</PopoverContent>
		</Popover>
	),
};

export const Playground: Story = {
	render: function PlaygroundRender() {
		const [{ open, modal, align, sideOffset }, updateArgs] = useArgs<PopoverStoryArgs>();

		return (
			<Popover
				open={open}
				modal={modal}
				onOpenChange={(next) => {
					updateArgs({ open: next });
				}}
			>
				<PopoverTrigger asChild>
					<TriggerButton>Toggle popover</TriggerButton>
				</PopoverTrigger>
				<PopoverContent align={align} sideOffset={sideOffset}>
					<PopoverHeader>
						<PopoverTitle>Playground</PopoverTitle>
						<PopoverDescription>Use Controls to change props.</PopoverDescription>
					</PopoverHeader>
					<div className="text-sm">Click the trigger or toggle the `open` control.</div>
				</PopoverContent>
			</Popover>
		);
	},
};

export const DefaultOpen: Story = {
	render: () => (
		<Popover defaultOpen>
			<PopoverTrigger asChild>
				<TriggerButton>Default open</TriggerButton>
			</PopoverTrigger>
			<PopoverContent align="start">
				<PopoverHeader>
					<PopoverTitle>Default open</PopoverTitle>
					<PopoverDescription>Useful for docs/snapshots.</PopoverDescription>
				</PopoverHeader>
				<div className="text-sm">Popover content is visible immediately.</div>
			</PopoverContent>
		</Popover>
	),
};

export const WithHeader: Story = {
	render: () => (
		<Popover>
			<PopoverTrigger asChild>
				<TriggerButton>With header</TriggerButton>
			</PopoverTrigger>
			<PopoverContent align="start">
				<PopoverHeader>
					<PopoverTitle>Processing details</PopoverTitle>
					<PopoverDescription>Times are shown in your local timezone.</PopoverDescription>
				</PopoverHeader>
				<div className="d-grid gap-2">
					<div className="d-flex justify-content-between gap-3">
						<span className="text-muted-foreground text-sm">Created</span>
						<span className="text-sm">2026-02-07 20:15</span>
					</div>
					<div className="d-flex justify-content-between gap-3">
						<span className="text-muted-foreground text-sm">Duration</span>
						<span className="text-sm">12s</span>
					</div>
				</div>
			</PopoverContent>
		</Popover>
	),
};

export const Alignments: Story = {
	render: () => (
		<div className="d-flex gap-3 flex-wrap align-items-center">
			<Popover>
				<PopoverTrigger asChild>
					<TriggerButton>Align start</TriggerButton>
				</PopoverTrigger>
				<PopoverContent align="start">
					<div className="text-sm">Aligned start</div>
				</PopoverContent>
			</Popover>

			<Popover>
				<PopoverTrigger asChild>
					<TriggerButton>Align center</TriggerButton>
				</PopoverTrigger>
				<PopoverContent align="center">
					<div className="text-sm">Aligned center</div>
				</PopoverContent>
			</Popover>

			<Popover>
				<PopoverTrigger asChild>
					<TriggerButton>Align end</TriggerButton>
				</PopoverTrigger>
				<PopoverContent align="end">
					<div className="text-sm">Aligned end</div>
				</PopoverContent>
			</Popover>
		</div>
	),
};
