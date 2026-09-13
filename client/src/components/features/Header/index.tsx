import * as React from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import classNames from 'classnames';
import SocketStatusIndicator from '@/components/features/SocketStatusIndicator';
import { Button } from '@/components/shared/Button';
import { useAuth } from '@/hooks/useAuth';
import styles from './Header.module.css';
import { NavGroup } from './NavGroup';

const MENU_ID = 'primary-navigation';

const navLinkClass = ({ isActive }: { isActive: boolean }) => classNames(styles.link, isActive && styles.linkActive);

const Header: React.FC = () => {
	const { user, isAuthenticated, isLoading, logout } = useAuth();
	const [isMenuOpen, setIsMenuOpen] = React.useState(false);
	const { pathname } = useLocation();

	React.useEffect(() => {
		setIsMenuOpen(false);
	}, [pathname]);

	return (
		<header className={styles.header}>
			<nav className={styles.nav} aria-label="Main">
				<Link to="/" className={styles.brand}>
					JPLearning
				</Link>

				<button
					type="button"
					className={styles.toggle}
					aria-expanded={isMenuOpen}
					aria-controls={MENU_ID}
					aria-label={isMenuOpen ? 'Close navigation' : 'Open navigation'}
					onClick={() => setIsMenuOpen((open) => !open)}
				>
					<span className={styles.toggleBars} aria-hidden="true" />
				</button>

				<div id={MENU_ID} className={classNames(styles.menu, isMenuOpen && styles.menuOpen)}>
					<ul className={classNames(styles.list, styles.primary)}>
						<li>
							<NavLink className={navLinkClass} to="/articles">
								Articles
							</NavLink>
						</li>
						<li>
							<NavLink className={navLinkClass} to="/catalogues">
								Catalogues
							</NavLink>
						</li>
						<NavGroup label="Japanese Material" id="material-nav-group">
							<li>
								<NavLink className={navLinkClass} to="/radicals">
									Radicals
								</NavLink>
							</li>
							<li>
								<NavLink className={navLinkClass} to="/kanjis">
									Kanjis
								</NavLink>
							</li>
							<li>
								<NavLink className={navLinkClass} to="/words">
									Words
								</NavLink>
							</li>
							<li>
								<NavLink className={navLinkClass} to="/sentences">
									Sentences
								</NavLink>
							</li>
						</NavGroup>
						<li>
							<NavLink className={navLinkClass} to="/community">
								Community
							</NavLink>
						</li>
						{isAuthenticated && (
							<>
								<li>
									<NavLink className={navLinkClass} to="/dashboard">
										Dashboard
									</NavLink>
								</li>
								<NavGroup label="New" id="new-nav-group">
									<li>
										<NavLink className={navLinkClass} to="/newarticle">
											Article
										</NavLink>
									</li>
									<li>
										<NavLink className={navLinkClass} to="/catalogues/new">
											Catalogue
										</NavLink>
									</li>
									<li role="separator" className={styles.divider} />
									<li>
										<NavLink className={navLinkClass} to="/newpost">
											Community Post
										</NavLink>
									</li>
								</NavGroup>
							</>
						)}
					</ul>

					{isLoading ? (
						<ul className={classNames(styles.list, styles.account)} aria-label="Account status">
							<li>
								<span className={styles.authPending} aria-label="Checking account status" />
							</li>
						</ul>
					) : isAuthenticated && user ? (
						<ul className={classNames(styles.list, styles.account)} aria-label="Account">
							<li className={styles.socket}>
								<SocketStatusIndicator />
							</li>
							<li>
								<NavLink className={navLinkClass} to="/dashboard">
									Logged in as <strong className={styles.userName}>{user.name}</strong>
								</NavLink>
							</li>
							<li>
								<Button type="button" variant="outline" size="sm" onClick={() => logout()}>
									Logout
								</Button>
							</li>
						</ul>
					) : (
						<ul className={classNames(styles.list, styles.account)} aria-label="Account">
							<li>
								<NavLink className={navLinkClass} to="/register">
									Sign Up
								</NavLink>
							</li>
							<li>
								<NavLink className={navLinkClass} to="/login">
									Log In
								</NavLink>
							</li>
						</ul>
					)}
				</div>
			</nav>
		</header>
	);
};

export default Header;
