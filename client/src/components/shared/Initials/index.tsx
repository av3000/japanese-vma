import * as React from 'react';
import classNames from 'classnames';
import styles from './Initials.module.css';

/**
 * Up to two initials from a display name: the first character of the first and last words. A name
 * with no spaces (most Japanese names) gives one character. Characters are taken by code point, so a
 * name starting with a character outside the BMP is not split in half.
 */
export const initialsOf = (name: string | null | undefined): string => {
	const words = (name ?? '').trim().split(/\s+/).filter(Boolean);

	if (words.length === 0) return '?';

	const first = Array.from(words[0])[0];
	const last = words.length > 1 ? Array.from(words[words.length - 1])[0] : '';

	return `${first}${last}`.toLocaleUpperCase();
};

export interface InitialsProps {
	name: string | null | undefined;
	size?: 'sm' | 'md';
	className?: string;
}

/**
 * A neutral circle with a person's initials. Always decorative: the name is shown beside it, so
 * assistive technology would only hear it twice.
 */
export const Initials: React.FC<InitialsProps> = ({ name, size = 'md', className }) => (
	<span className={classNames(styles.initials, styles[size], className)} aria-hidden="true">
		{initialsOf(name)}
	</span>
);

export default Initials;
