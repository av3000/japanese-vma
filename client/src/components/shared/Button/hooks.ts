import classNames from "classnames";
import styles from "./Button.module.scss";
import { ButtonBaseProps } from "./types";

const capitalize = (word: string): string =>
  word.length > 0 ? word.charAt(0).toUpperCase() + word.slice(1) : word;

export const useButtonClassNames = (
  {
    hasNoPaddingX,
    hasOnlyIcon,
    isFullWidth,
    ctaGroupPos,
    disabled,
    isLoading,
    size,
    variant,
  }: Omit<ButtonBaseProps, "aria-label">,
  className?: string
): string => {
  return classNames(
    {
      [styles.button]: true,
      [styles.fullWidth]: isFullWidth,
      [styles.hasOnlyIcon]: hasOnlyIcon,
      [styles.hasNoPaddingX]: hasNoPaddingX,
      [styles.disabled]: disabled || isLoading,
      [`u-cta-group-${ctaGroupPos}`]: ctaGroupPos !== undefined,
    },
    variant !== undefined && styles[`variant${capitalize(variant)}`],
    size !== undefined && styles[`size${capitalize(size)}`],
    className
  );
};
