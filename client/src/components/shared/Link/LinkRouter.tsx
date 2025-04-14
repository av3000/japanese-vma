import * as React from 'react';
import { Link as RouterLink, LinkProps as RouterLinkProps } from 'react-router-dom';

import { useLinkClassNames } from './hooks';
import { LinkBaseProps, LinkColor } from './types';

export interface LinkRouterProps extends LinkBaseProps, Partial<RouterLinkProps> {
  readonly color?: LinkColor;
  readonly route?: any;
}

export const LinkRouter: React.FunctionComponent<LinkRouterProps> = ({
  children,
  className,
  to,
  target,
  route,
  title,
  textLink,
  richTextLink,
  color,
  size,
  weight,
  hideExternalIcon,
  hasMinHeight,
  ...routerLinkProps // For passing props inherited from RouterLink
}) => {
  const cssClasses = useLinkClassNames(
    {
      color,
      hasMinHeight,
      hideExternalIcon,
      richTextLink,
      size,
      textLink,
      weight,
    },
    className,
  );

  return (
    <RouterLink
      className={cssClasses}
      to={to ?? route?.externalRoute ?? ''}
      state={route}
      target={target}
      title={title}
      {...routerLinkProps}
    >
      {children}
    </RouterLink>
  );
};
