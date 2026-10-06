import classNames from 'classnames';
import styles from './Header.module.css';

export { DICTIONARY_LINKS, EXPLORE_LINKS, NAV_SECTIONS } from '@/shared/constants/navigation';
export type { NavItem } from '@/shared/constants/navigation';

export const navLinkClass = ({ isActive }: { isActive: boolean }) =>
	classNames(styles.link, isActive && styles.linkActive);
