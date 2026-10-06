import * as React from 'react';
import { Link } from 'react-router-dom';
import classNames from 'classnames';
import { Icon, type IconName } from '@/components/shared/Icon';
import styles from './LibraryCard.module.css';

/** Tags shown before the rest collapse into "+N". */
export const MAX_CARD_TAGS = 3;

export const CARD_STATS = {
	views: { icon: 'eyeRegular', label: 'views' },
	comments: { icon: 'commentSolid', label: 'comments' },
	likes: { icon: 'thumbsUpSolid', label: 'likes' },
	downloads: { icon: 'downloadSolid', label: 'downloads' },
} as const satisfies Record<string, { icon: IconName; label: string }>;

export type CardStatKind = keyof typeof CARD_STATS;

const countFormat = new Intl.NumberFormat('en-US');

/** Engagement counts arrive as numeric strings; anything unreadable counts as 0. */
export const toCount = (value: string | number | null | undefined): number => {
	const count = Number(value ?? 0);

	return Number.isFinite(count) && count > 0 ? count : 0;
};

const FIRST_LETTER = /\p{L}/u;
const JAPANESE = /[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}]/u;

/**
 * `ja` when the text starts in Japanese (its first letter is a kanji or kana), so Japanese titles
 * get Japanese line breaking and fonts. An English title that quotes は or が stays English:
 * marking it `ja` would set its Latin text in the Japanese font.
 */
export const japaneseLang = (text: string): 'ja' | undefined => {
	const first = text.match(FIRST_LETTER)?.[0];

	return first && JAPANESE.test(first) ? 'ja' : undefined;
};

export const LibraryCard: React.FC<{ cover: React.ReactNode; children: React.ReactNode; className?: string }> = ({
	cover,
	children,
	className,
}) => (
	<article className={classNames(styles.card, className)}>
		{cover}
		<div className={styles.body}>{children}</div>
	</article>
);

/** The card's heading. Its link is stretched over the whole card, so the card is one link. */
export const CardTitle: React.FC<{ to: string; lang?: string; children: React.ReactNode }> = ({
	to,
	lang,
	children,
}) => (
	<h2 className={styles.title}>
		<Link to={to} lang={lang} className={styles.titleLink}>
			{children}
		</Link>
	</h2>
);

export const CardDate: React.FC<{ dateTime: string; children: React.ReactNode }> = ({ dateTime, children }) => (
	<time className={styles.date} dateTime={dateTime}>
		{children}
	</time>
);

export const CardSubtitle: React.FC<{ lines?: 1 | 2; lang?: string; children: React.ReactNode }> = ({
	lines = 1,
	lang,
	children,
}) => (
	<p className={classNames(styles.subtitle, lines === 2 && styles.subtitleTwoLines)} lang={lang}>
		{children}
	</p>
);

export const CardOwner: React.FC<{ name: string }> = ({ name }) => <span className={styles.owner}>by {name}</span>;

/** Up to `MAX_CARD_TAGS` tags, then "+N". Plain text: the card is already one link. */
export const CardTags: React.FC<{ tags: ReadonlyArray<{ id: number | string; content: string }> }> = ({ tags }) => {
	if (tags.length === 0) return null;

	const hidden = tags.length - MAX_CARD_TAGS;

	return (
		<ul className={styles.tags} aria-label="Tags">
			{tags.slice(0, MAX_CARD_TAGS).map((tag) => (
				<li key={tag.id} className={styles.tag} title={tag.content}>
					{tag.content}
				</li>
			))}
			{hidden > 0 && (
				<li className={styles.tagMore}>
					<span aria-hidden="true">+{hidden}</span>
					<span className={styles.visuallyHidden}>{hidden} more</span>
				</li>
			)}
		</ul>
	);
};

/** The footer: each count with its icon, read as "1,284 views". */
export const CardStats: React.FC<{ stats: ReadonlyArray<{ kind: CardStatKind; count: number }> }> = ({ stats }) => (
	<ul className={styles.stats} aria-label="Stats">
		{stats.map(({ kind, count }) => (
			<li key={kind} className={styles.stat} data-stat={kind}>
				<Icon size="sm" name={CARD_STATS[kind].icon} />
				{countFormat.format(count)}
				<span className={styles.visuallyHidden}> {CARD_STATS[kind].label}</span>
			</li>
		))}
	</ul>
);
