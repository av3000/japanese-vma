import * as React from 'react';
import { Link, NavLink } from 'react-router-dom';
import classNames from 'classnames';
import SocketStatusIndicator from '@/components/features/SocketStatusIndicator';
import { Button } from '@/components/shared/Button';
import { Drawer } from '@/components/shared/Drawer';
import { Icon } from '@/components/shared/Icon';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { AccountMenu } from './AccountMenu';
import styles from './Header.module.css';
import { NAV_SECTIONS } from './navigation';

const drawerLinkClass = ({ isActive }: { isActive: boolean }) =>
	classNames(styles.drawerLink, isActive && styles.linkActive);

/**
 * Below 1024px: the menu button and a full-height navigation drawer. Explore and Dictionary are
 * labelled flat sections; the account is pinned last (the name as a disclosure for a signed-in
 * user, Sign Up and Log In for a guest). Escape or the close button returns focus to the button.
 */
export const MenuDrawer: React.FC = () => {
	const { user, isAuthenticated, isLoading, logout } = useAuth();
	const dialogRef = React.useRef<HTMLDialogElement>(null);
	const modal = useModal(dialogRef, { transitionMs: 0 });
	const sectionId = React.useId();

	let account: React.ReactNode;
	if (isLoading) {
		account = <span className={styles.authPending} aria-label="Checking account status" />;
	} else if (isAuthenticated && user) {
		account = (
			<ul className={styles.drawerAccountList} aria-label="Account">
				<li className={styles.socket}>
					<SocketStatusIndicator />
				</li>
				<AccountMenu
					id="drawer-account-nav-group"
					name={user.name}
					onLogout={() => logout()}
					onSelect={modal.close}
					className={styles.drawerAccountGroup}
				/>
			</ul>
		);
	} else {
		account = (
			<div className={styles.drawerGuest}>
				<Button to="/register" variant="primary" className={styles.drawerGuestAction} onClick={modal.close}>
					Sign Up
				</Button>
				<Link
					to="/login"
					className={classNames(styles.drawerGuestAction, styles.drawerLogin)}
					onClick={modal.close}
				>
					Log In
				</Link>
			</div>
		);
	}

	return (
		<>
			<button
				type="button"
				className={styles.menuButton}
				aria-label="Open navigation"
				aria-haspopup="dialog"
				aria-expanded={modal.isOpen}
				aria-controls={modal.id}
				onClick={modal.open}
			>
				<span className={styles.menuBars} aria-hidden="true" />
			</button>

			<Drawer modal={modal} label="Navigation">
				<div className={styles.drawerTop}>
					<Link to="/" className={styles.brand} onClick={modal.close}>
						JPLearning
					</Link>
					<button
						type="button"
						className={styles.iconButton}
						aria-label="Close navigation"
						onClick={modal.close}
					>
						<Icon name="removeSolid" size="sm" />
					</button>
				</div>

				<nav aria-label="Main" className={styles.drawerNav}>
					{NAV_SECTIONS.map((section) => (
						<section key={section.id} className={styles.drawerSection}>
							<h2 id={`${sectionId}-${section.id}`} className={styles.drawerHeading}>
								{section.label}
							</h2>
							<ul className={styles.drawerList} aria-labelledby={`${sectionId}-${section.id}`}>
								{section.links.map((link) => (
									<li key={link.to}>
										<NavLink className={drawerLinkClass} to={link.to} onClick={modal.close}>
											{link.label}
										</NavLink>
									</li>
								))}
							</ul>
						</section>
					))}
				</nav>

				<div className={styles.drawerAccount}>{account}</div>
			</Drawer>
		</>
	);
};
