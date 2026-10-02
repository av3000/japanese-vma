import * as React from 'react';
import { NavLink } from 'react-router-dom';
import classNames from 'classnames';
import styles from './Header.module.css';
import { NavGroup } from './NavGroup';
import { navLinkClass } from './navigation';

interface AccountMenuProps {
	name: string;
	/** DOM id for the disclosed list; the Header renders one in the bar and one in the menu drawer. */
	id: string;
	onLogout: () => void;
	/** Called after any item is used, e.g. to close the menu drawer. */
	onSelect?: () => void;
	className?: string;
}

/**
 * The signed-in user's name as a disclosure: Dashboard, a divider, then Log out as the
 * destructive last item. A disclosure with a list, not an ARIA menu, like the nav groups.
 */
export const AccountMenu: React.FC<AccountMenuProps> = ({ name, id, onLogout, onSelect, className }) => (
	<NavGroup
		id={id}
		label={<span className={styles.userName}>{name}</span>}
		className={classNames(styles.accountGroup, className)}
		buttonClassName={styles.accountButton}
	>
		<li>
			<NavLink className={navLinkClass} to="/dashboard" onClick={onSelect}>
				Dashboard
			</NavLink>
		</li>
		<li role="separator" className={styles.divider} />
		<li>
			<button
				type="button"
				className={classNames(styles.link, styles.danger)}
				onClick={() => {
					onLogout();
					onSelect?.();
				}}
			>
				Log out
			</button>
		</li>
	</NavGroup>
);
