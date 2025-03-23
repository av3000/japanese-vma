const getAllCssVars = (
  prefix: string | undefined = undefined,
  styleSheets = document.styleSheets
): string[] => {
  const cssVars: string[] = [];

  for (let i = 0; i < styleSheets.length; i++) {
    // try/catch used because 'hasOwnProperty' doesn't work
    try {
      for (let j = 0; j < styleSheets[i].cssRules.length; j++) {
        try {
          const cssRule = styleSheets[i].cssRules[j];

          if (!(cssRule instanceof CSSStyleRule)) {
            continue;
          }

          // loop stylesheet's cssRules' style (property names)
          for (let k = 0; k < cssRule.style.length; k++) {
            const name = cssRule.style[k];
            // test name for css variable signature, optional prefix and uniqueness
            if (
              name.startsWith(`--${prefix || ""}`) &&
              !cssVars.includes(name)
            ) {
              cssVars.push(name);
            }
          }
        } catch {
          /* empty */
        }
      }
    } catch {
      /* empty */
    }
  }

  return cssVars;
};

export const getElemCSSVars = (
  prefix: string | undefined = undefined,
  element = document.documentElement,
  pseudo = undefined
): Record<string, string> => {
  const allCSSVars = getAllCssVars(prefix);
  const elStyles = window.getComputedStyle(element, pseudo);
  const cssVars: Record<string, string> = {};

  for (let i = 0; i < allCSSVars.length; i++) {
    const key = allCSSVars[i];
    const value = elStyles.getPropertyValue(key);

    if (value) {
      cssVars[key] = value;
    }
  }
  return cssVars;
};
