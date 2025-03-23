import * as React from "react";
import { useButtonClassNames } from "./hooks";
import { ButtonBaseProps } from "./types";

export type ButtonAnchorProps = React.AnchorHTMLAttributes<HTMLAnchorElement> &
  ButtonBaseProps;

/**
 * Button that is rendered as a HTML anchor
 */
export const ButtonAnchor: React.FunctionComponent<ButtonAnchorProps> = ({
  className,
  variant,
  size,
  isFullWidth,
  hasOnlyIcon,
  ctaGroupPos,
  children,
  hasNoPaddingX,
  disabled,
  isLoading,
  ...anchorProps
}) => {
  const classes = useButtonClassNames(
    {
      variant,
      size,
      isFullWidth,
      hasOnlyIcon,
      ctaGroupPos,
      hasNoPaddingX,
      disabled,
      isLoading,
    },
    className
  );

  return (
    <a className={classes} {...anchorProps}>
      {children}
    </a>
  );
};
