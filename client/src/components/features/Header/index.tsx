import * as React from 'react';
import { Link, NavLink } from 'react-router-dom';
import { HeaderSearch } from '@/components/features/HeaderSearch';
import { LanguageSelector } from '@/components/features/LanguageSelector';
import SocketStatusIndicator from '@/components/features/SocketStatusIndicator';
import { Button } from '@/components/shared/Button';
import { Logo } from '@/components/shared/Logo';
import { useAuth } from '@/hooks/useAuth';
import { useTranslation } from '@/hooks/useTranslation';
import { AccountMenu } from './AccountMenu';
import { ColorSchemeToggle } from './ColorSchemeToggle';
import styles from './Header.module.css';
import { MenuDrawer } from './MenuDrawer';
import { NavGroup } from './NavGroup';
import { NAV_SECTIONS, navLinkClass } from './navigation';

/**
 * The app bar on every page. From 1024px: brand, the Explore and Dictionary disclosures, the
 * search panel, the account, the language selector (once a second language is active) and the colour-scheme toggle. Below: brand, the search button, the toggle
 * and the menu button, the search and menu buttons each opening a full-height drawer.
 */
const Header: React.FC = () => {
	const { user, isAuthenticated, isLoading, logout } = useAuth();
	const { t } = useTranslation();

	let account: React.ReactNode;
	if (isLoading) {
		account = (
			<ul className={styles.account} aria-label={t('account.status_label')}>
				<li>
					<span className={styles.authPending} role="status" aria-label={t('account.checking_status')} />
				</li>
			</ul>
		);
	} else if (isAuthenticated && user) {
		account = (
			<ul className={styles.account} aria-label={t('account.nav_label')}>
				<li className={styles.socket}>
					<SocketStatusIndicator />
				</li>
				<AccountMenu id="account-nav-group" name={user.name} onLogout={() => logout()} />
			</ul>
		);
	} else {
		account = (
			<ul className={styles.account} aria-label={t('account.nav_label')}>
				<li>
					<NavLink className={navLinkClass} to="/login">
						{t('account.log_in')}
					</NavLink>
				</li>
				<li>
					<Button to="/register" variant="primary" size="sm">
						{t('account.sign_up')}
					</Button>
				</li>
			</ul>
		);
	}

	return (
		<header className={styles.header}>
			<div className={styles.bar}>
				<Link to="/" className={styles.brand}>
					<Logo />
				</Link>

				<nav aria-label="Main" className={styles.desktopNav}>
					<ul className={styles.groups}>
						{NAV_SECTIONS.map((section) => (
							<NavGroup key={section.id} label={section.label} id={`${section.id}-nav-group`}>
								{section.links.map((link) => (
									<li key={link.to}>
										<NavLink className={navLinkClass} to={link.to}>
											<span>{link.label}</span>
											{link.description && (
												<>
													{' '}
													<span className={styles.description}>{link.description}</span>
												</>
											)}
										</NavLink>
									</li>
								))}
							</NavGroup>
						))}
					</ul>
				</nav>

				<HeaderSearch />

				{account}

				<div className={styles.language}>
					<LanguageSelector />
				</div>

				<ColorSchemeToggle />

				<MenuDrawer />
			</div>
		</header>
	);
};

export default Header;
