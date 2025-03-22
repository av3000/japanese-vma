import React from "react";
import { ButtonAnchor, ButtonAnchorProps } from "./ButtonAnchor";
import { ButtonRegular, ButtonRegularProps } from "./ButtonRegular";
import { ButtonRouterLink, ButtonRouterLinkProps } from "./ButtonRouterLink";

export type ButtonProps = ButtonAnchorProps &
  ButtonRegularProps &
  ButtonRouterLinkProps;

/**
 * Generic Button component, can be rendered as button or anchor, depending on props passed to it.
 */
export const Button: React.FunctionComponent<ButtonProps> = ({
  href,
  children,
  route,
  to,
  type,
  ...buttonProps
}) => {
  if (to || route) {
    return (
      <ButtonRouterLink to={to} route={route} {...buttonProps}>
        {children}
      </ButtonRouterLink>
    );
  }

  if (href) {
    return (
      <ButtonAnchor href={href} {...buttonProps}>
        {children}
      </ButtonAnchor>
    );
  }

  return (
    <ButtonRegular type={type} {...buttonProps}>
      {children}
    </ButtonRegular>
  );
};
