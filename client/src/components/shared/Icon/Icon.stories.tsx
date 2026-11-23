import type { Meta, StoryObj } from '@storybook/react-vite';
import { Icon, iconNames, iconRotations, iconSizes } from '.';
import { expect, within } from 'storybook/test';
import { IconGetter } from '@/storybook/helpers/IconGetter';

const meta = {
	title: 'Shared/Icon',
	component: Icon,
	args: { name: 'user', size: 'md' },
	argTypes: {
		name: { control: 'select', options: iconNames },
		size: { control: 'inline-radio', options: iconSizes },
		mobileSize: { control: 'inline-radio', options: [undefined, ...iconSizes] },
		rotate: { control: 'inline-radio', options: [undefined, ...iconRotations] },
	},
} satisfies Meta<typeof Icon>;

export default meta;

type Story = StoryObj<typeof meta>;

const row = { display: 'flex', flexWrap: 'wrap', gap: '1.5rem', alignItems: 'flex-end' } as const;
const cell = { display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '0.5rem' } as const;

/** Icons are decorative: they are hidden from assistive technology, so the label is always beside them. */
export const Playground: Story = {};

export const Sizes: Story = {
	render: (args) => (
		<div style={row}>
			{iconSizes.map((size) => (
				<div key={size} style={cell}>
					<Icon {...args} size={size} />
					<span>{size}</span>
				</div>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const size of iconSizes) {
			await expect(canvas.getByText(size)).toBeVisible();
		}
	},
};

/** The chevron turned by 90, 180 and 270 degrees. */
export const Rotations: Story = {
	args: { name: 'chevron' },
	render: (args) => (
		<div style={row}>
			<div style={cell}>
				<Icon {...args} />
				<span>none</span>
			</div>
			{iconRotations.map((rotate) => (
				<div key={rotate} style={cell}>
					<Icon {...args} rotate={rotate} />
					<span>{rotate}</span>
				</div>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const rotate of iconRotations) {
			await expect(canvas.getByText(rotate)).toBeVisible();
		}
	},
};

/** Every built-in icon, by its name. */
export const Gallery: Story = {
	parameters: { layout: 'padded' },
	render: () => <IconGetter />,
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByRole('listitem')).toHaveLength(iconNames.length);
	},
};
