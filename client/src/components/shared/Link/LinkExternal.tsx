import * as React from 'react';

import { Icon } from '../Icon';
import { useLinkClassNames } from './hooks';
import { LinkBaseProps, LinkColor } from './types';

export interface LinkExternalProps
  extends LinkBaseProps,
    React.AnchorHTMLAttributes<HTMLAnchorElement> {
  readonly color?: LinkColor;
  readonly linkUrl?: string;
  readonly mailTo?: string;
  readonly hideExternalIcon?: boolean;
  readonly ExternalIcon?: React.FC<LinkExternalProps>;
}

const DefaultExternalIcon: React.FC<LinkExternalProps> = ({ size }) => (
  <Icon className="u-ml-3xs" name="chevron" rotate="90" size={size === 'sm' ? 'sm' : 'md'} />
);

export const LinkExternal: React.FunctionComponent<LinkExternalProps> = (props) => {
  const {
    children,
    className,
    linkUrl,
    target = '_blank',
    title,
    mailTo,
    textLink,
    richTextLink,
    color,
    size,
    weight,
    hideExternalIcon,
    hasMinHeight,
    ExternalIcon = DefaultExternalIcon,
    ...anchorProps // For passing props inherited from HTMLAnchorElement
  } = props;
  const cssClasses = useLinkClassNames(
    {
      color,
      hasMinHeight,
      richTextLink,
      size,
      textLink,
      weight,
    },
    className,
  );

  if (linkUrl !== undefined || mailTo !== undefined) {
    const showExternalIcon =
      target === '_blank' && typeof children === 'string' && !hideExternalIcon;

    return (
      <a
        className={cssClasses}
        href={mailTo ? `mailto:${mailTo}` : linkUrl}
        target={target}
        title={title}
        {...anchorProps}
      >
        {children}
        {showExternalIcon && <ExternalIcon {...props} />}
      </a>
    );
  }

  return (
    <span className={cssClasses} title={title} {...anchorProps}>
      {children}
    </span>
  );
};
