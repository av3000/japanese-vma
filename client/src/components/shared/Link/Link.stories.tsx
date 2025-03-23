import type { Meta, StoryObj } from "@storybook/react";
import { Link } from "../Link";

const meta: Meta<typeof Link> = {
  component: Link,
  tags: ["autodocs"],
};

export default meta;
type Story = StoryObj<typeof Link>;

export const SetUrlViaRoute: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    route: {
      externalRoute: "/en/some-category",
    },
    linkUrl: "/en/some-category",
  },
};

export const SetUrlViaToSimple: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
  },
};

export const SetUrlViaToWithState: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/some-category",
    state: {
      externalRoute: "/en/some-category",
    },
  },
};

export const SetUrlViaLinkUrl: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    linkUrl: "https://google.com",
  },
};

export const HasTarget: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    linkUrl: "https://google.com",
    target: "_blank",
  },
};

export const HasTitle: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    title: "Links title shown on hover",
  },
};

export const HasTextLinkStyles: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    textLink: true,
  },
};

export const HasRichTextLinkStyles: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    richTextLink: true,
  },
};

export const HasMinHeightStyles: Story = {
  render: (args) => <Link {...args}>Min height to increase click area</Link>,
  args: {
    to: "/en/cms-demo",
    hasMinHeight: true,
  },
};

export const IsColorDefault: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    color: "default",
  },
};

export const IsSizeSm: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    size: "sm",
  },
};

export const IsSizeMd: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    size: "md",
  },
};

export const IsSizeLg: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    size: "lg",
  },
};

export const IsWeightRegular: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    weight: "regular",
  },
};

export const IsWeightBold: Story = {
  render: (args) => <Link {...args}>Some link</Link>,
  args: {
    to: "/en/cms-demo",
    weight: "bold",
  },
};
