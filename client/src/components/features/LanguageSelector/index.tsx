import * as React from 'react';
import classNames from 'classnames';
import { Select } from '@/components/shared/FormControls';
import { useTranslation } from '@/hooks/useTranslation';
import styles from './LanguageSelector.module.css';

interface LanguageSelectorProps {
	className?: string;
	/** Where the failure notice sits: `floating` under the control in the header bar, `inline` in the menu drawer. */
	notice?: 'floating' | 'inline';
}

/**
 * The header's language picker: a native `<select>`, so the keyboard and screen readers get it for
 * free, labelled "Language", with each option written in its own language ("Dansk"). Renders nothing
 * until a second language is active (FR-15), so an English-only site looks exactly as before.
 *
 * A failed load leaves the labels as they were and says so in a polite status line, not an alert: it
 * never blocks the page (NFR-06). The status element is always present, so the announcement is made
 * when its text appears.
 */
export const LanguageSelector: React.FC<LanguageSelectorProps> = ({ className, notice = 'floating' }) => {
	const { locale, displayedLocale, locales, setLocale, error } = useTranslation();
	const id = React.useId();

	if (locales.length < 2) {
		return null;
	}

	const displayedName = locales.find((option) => option.code === displayedLocale)?.name ?? displayedLocale;

	return (
		<div className={classNames(styles.selector, className)}>
			<label htmlFor={id} className={styles.label}>
				Language
			</label>
			<Select
				id={id}
				size="sm"
				className={styles.select}
				value={locale}
				onChange={(event) => setLocale(event.target.value)}
			>
				{locales.map((option) => (
					<option key={option.code} value={option.code} lang={option.code}>
						{option.name}
					</option>
				))}
			</Select>
			<p
				role="status"
				className={classNames(styles.notice, error && notice === 'floating' && styles.noticeFloating)}
			>
				{error && `Couldn't load that language. Showing ${displayedName}.`}
			</p>
		</div>
	);
};
