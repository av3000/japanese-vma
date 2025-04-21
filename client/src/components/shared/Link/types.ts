export type LinkColor = 'default';

export const linkSizes = ['sm', 'md', 'lg'] as const;

export type LinkSize = (typeof linkSizes)[number];

export const linkWeights = ['regular', 'bold'] as const;

export type LinkWeightType = (typeof linkWeights)[number];

export interface LinkBaseProps {
  readonly textLink?: boolean;
  readonly richTextLink?: boolean;
  readonly color?: LinkColor;
  readonly size?: LinkSize;
  readonly weight?: LinkWeightType;
  readonly hideExternalIcon?: boolean;
  readonly hasMinHeight?: boolean;
}
