import * as React from "react";
import classNames from "classnames";
import styles from "./Button.module.scss";
import { useButtonClassNames } from "./hooks";
import { ButtonBaseProps } from "./types";

export type ButtonRegularProps = React.ButtonHTMLAttributes<HTMLButtonElement> &
  ButtonBaseProps;

/**
 * Button that is rendered as a regular HTML button
 */
export const ButtonRegular: React.FunctionComponent<ButtonRegularProps> = ({
  className,
  variant,
  size,
  isFullWidth,
  hasOnlyIcon,
  ctaGroupPos,
  children,
  type = "button",
  hasNoPaddingX,
  disabled,
  isLoading,
  ...buttonProps
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
    <button
      className={classes}
      type={type}
      disabled={disabled || isLoading}
      {...buttonProps}
    >
      {isLoading && (
        <span className={classNames(styles.spinner, "u-spinner")} />
      )}
      {isLoading === undefined ? (
        <>{children}</>
      ) : (
        <span className={styles.content}>{children}</span>
      )}
    </button>
  );
};
