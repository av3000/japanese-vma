import React from 'react';

import { Link as RouterLink, LinkProps as RouterLinkProps } from 'react-router-dom';
import { useButtonClassNames } from './hooks';
import { ButtonBaseProps } from './types';

export type ButtonRouterLinkProps = Partial<RouterLinkProps> &
  ButtonBaseProps & {
    /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
    readonly route?: any;
  };

/**
 * Button used for navigating router links.
 *
 * Renders react-router's Link directly rather than the shared `Link`: that adds Link's own colour
 * and transition classes, which override the button variant's text colour depending on CSS order.
 */
export const ButtonRouterLink: React.FunctionComponent<ButtonRouterLinkProps> = ({
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
    className,
  );

  return (
    <RouterLink className={classes} to={to ?? route?.externalRoute ?? ''} state={route} {...routerLinkProps}>
      {children}
    </RouterLink>
  );
};
