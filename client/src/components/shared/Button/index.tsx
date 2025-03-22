import React from "react";
import styles from "./Button.module.scss";

interface ButtonProps {
  variant: "primary" | "secondary";
  size: "sm" | "md" | "lg";
  children: React.ReactNode;
  disabled?: boolean;
  onClick?: () => void;
}

const Button: React.FC<ButtonProps> = ({
  variant = "primary",
  size = "md",
  children,
  disabled,
  onClick,
}) => {
  const variantClass =
    styles[`button--${variant}`] || styles["button--primary"];
  const sizeClass = styles[`button--${size}`] || styles["button--medium"];

  return (
    <button
      className={`${styles.button} ${variantClass} ${sizeClass}`}
      disabled={disabled}
      onClick={() => {
        if (!disabled && onClick) {
          onClick();
        }

        console.log("Button clicked!");
      }}
      type="button"
    >
      {children}
    </button>
  );
};

export default Button;
