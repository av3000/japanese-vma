import React from "react";
import { Link, LinkProps } from "../Link";
import { useButtonClassNames } from "./hooks";
import { ButtonBaseProps } from "./types";

export type ButtonRouterLinkProps = Partial<Omit<LinkProps, "size">> &
  ButtonBaseProps;

/**
 * Button used for navigating router links
 */
export const ButtonRouterLink: React.FunctionComponent<
  ButtonRouterLinkProps
> = ({
  className,
  variant,
  size,
  isFullWidth,
  hasOnlyIcon,
  ctaGroupPos,
  children,
  route,
  to,
  hasNoPaddingX,
  disabled,
  isLoading,
  ...routerLinkProps
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
    <Link
      className={classes}
      to={to ?? route?.externalRoute}
      state={route}
      {...routerLinkProps}
    >
      {children}
    </Link>
  );
};
