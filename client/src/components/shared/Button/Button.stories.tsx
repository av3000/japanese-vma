import type { Meta, StoryObj } from "@storybook/react";
import { Icon } from "@/components/shared/Icon";
import { Button } from "./Button";
import { buttonSizes, buttonVariants } from "./types";

const meta: Meta<typeof Button> = {
  component: Button,
  tags: ["autodocs"],
  argTypes: {
    size: {
      options: buttonSizes,
      control: { type: "radio" },
    },
    variant: {
      options: buttonVariants,
      control: { type: "radio" },
    },
    disabled: {
      control: { type: "boolean" },
    },
  },
};

export default meta;
type Story = StoryObj<typeof Button>;

export const Default: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const RenderAsAnchor: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    href: "#!",
  },
};

export const RenderAsRouterLink: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    route: {
      externalRoute: "/en/57bd5f8c-d69f-4d68-8333-494b0718604a",
    },
  },
};

export const IsPrimaryVariant: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    variant: "primary",
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const IsSecondaryVariant: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    variant: "secondary",
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const IsGhostVariant: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    variant: "ghost",
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const IsLinkButtonVariant: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    variant: "linkButton",
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const IsLoading: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    isLoading: true,
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const IsSmSize: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    size: "sm",
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const IsMdSize: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    size: "md",
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const IsFullWidth: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    isFullWidth: true,
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};
export const HasOnlyIcon: Story = {
  render: (args) => (
    <Button {...args}>
      <Icon name={"user"} size={"md"} />
    </Button>
  ),
  args: {
    hasOnlyIcon: true,
    "aria-label": "Some button", // Remember to add aria-label when only icon as button content
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const WithIcon: Story = {
  render: (args) => (
    <Button {...args}>
      <Icon name={"user"} size={"md"} />
      <span>Some button</span>
    </Button>
  ),
  args: {
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const HasNoPaddingX: Story = {
  render: (args) => (
    <Button {...args}>
      <Icon name={"user"} size={"md"} />
      <span>Some button</span>
    </Button>
  ),
  args: {
    variant: "ghost",
    hasNoPaddingX: true,
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const InCtaGroup: Story = {
  render: (args) => (
    <>
      <Button {...args} ctaGroupPos={"left"}>
        <Icon name={"user"} size={"md"} />
        <span>My profile</span>
      </Button>

      <Button {...args} ctaGroupPos={"center"}>
        <Icon name={"chevron"} size={"md"} />
        <span>Filters</span>
      </Button>

      <Button {...args} ctaGroupPos={"right"}>
        <Icon name={"plus"} size={"md"} />
        <span>To basket</span>
      </Button>
    </>
  ),
  args: {
    variant: "secondary",
    size: "sm",
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};

export const IsDisabled: Story = {
  render: (args) => <Button {...args}>Some button</Button>,
  args: {
    disabled: true,
    onClick: (): void => {
      console.log("button clicked");
    },
  },
};
