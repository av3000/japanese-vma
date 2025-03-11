import React from "react";
import styles from "./Button.module.scss";

const Button = ({ variant = "primary", size = "md", children, disabled }) => {
  const variantClass =
    styles[`button--${variant}`] || styles["button--primary"];
  const sizeClass = styles[`button--${size}`] || styles["button--medium"];

  return (
    <button
      className={`${styles.button} ${variantClass} ${sizeClass}`}
      disabled={disabled}
      onClick={() => {
        if (!disabled) {
          console.log("Button clicked!");
        }
      }}
      type="button"
    >
      {children}
    </button>
  );
};

export default Button;
