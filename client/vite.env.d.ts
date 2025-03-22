/// <reference types="vite/client" />

declare module "*.module.scss" {
  const styleClasses: { [key: string]: string };
  export default styleClasses;
}
