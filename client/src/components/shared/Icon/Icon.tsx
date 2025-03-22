import * as React from "react";
import classNames from "classnames";
import { icons } from "@/assets/icons";
import { capitalize } from "@/helpers";
import styles from "./Icon.module.scss";

export const iconNames = Object.keys(icons);
export type IconName = keyof typeof icons;

export const iconSizes = ["sm", "md", "lg"] as const;
export type IconSize = (typeof iconSizes)[number];

export const iconRotations = ["90", "180", "270"] as const;
export type IconRotation = (typeof iconRotations)[number];

export interface IconProps {
  readonly className?: string;
  readonly name: IconName;
  readonly mobileSize?: IconSize;
  readonly size: IconSize;
  readonly rotate?: IconRotation;
}

/**
 * Generic Icon component, "name" should be one of the built-in icons.
 */
export const Icon: React.FunctionComponent<IconProps> = ({
  mobileSize,
  size,
  name,
  className,
  rotate,
}) => {
  const availableIcons = icons as Record<string, string>;

  const iconClassNames = classNames(
    styles.icon,
    styles[`size${capitalize(size ?? "md")}`],
    mobileSize && styles[`mobileSize${capitalize(mobileSize)}`],
    className
  );

  return (
    <i
      className={iconClassNames}
      aria-hidden="true"
      dangerouslySetInnerHTML={{
        __html: availableIcons[name].replace(/<svg/, `<svg aria-hidden="true"`),
      }}
      style={
        {
          "--rotate": rotate !== undefined ? `${rotate}deg` : undefined,
        } as React.CSSProperties
      }
    />
  );
};
