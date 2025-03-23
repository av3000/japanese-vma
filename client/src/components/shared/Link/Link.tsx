import * as React from "react";
import { LinkExternal, LinkExternalProps } from "./LinkExternal";
import { LinkRouter, LinkRouterProps } from "./LinkRouter";

export interface LinkProps extends LinkRouterProps, LinkExternalProps {}

export const Link: React.FunctionComponent<LinkProps> = ({
  to,
  route,
  linkUrl,
  ...otherProps
}) => {
  if (to || route) {
    return <LinkRouter to={to} route={route} {...otherProps} />;
  }

  return <LinkExternal linkUrl={linkUrl} {...otherProps} />;
};
