import React, { useMemo, useState } from 'react';
import { getElemCSSVars } from '../settingsGetter';
import styles from './TokenGroup.module.css';

/**
 * How a token is previewed. Every kind reads the live custom property, so the page cannot drift
 * from `src/styles/00-settings/*.css`.
 *
 * - `bar`: a horizontal bar as wide as the token (spacing, container widths). Use `scale` for tokens wider than the column.
 * - `square`: a square as wide and tall as the token (`--size-*`, `--icon-size-*`).
 * - `radius`, `border`, `shadow`, `opacity`, `aspectRatio`: a swatch that applies the token.
 * - `fontSize`, `fontFamily`, `fontWeight`, `lineHeight`: sample text set in the token.
 * - `zIndex`: the resolved number (the tokens are `calc()` chains) and a step bar.
 * - `duration`, `easing`: a dot that travels along a track when played.
 */
export type TokenKind =
	| 'bar'
	| 'square'
	| 'radius'
	| 'border'
	| 'shadow'
	| 'opacity'
	| 'aspectRatio'
	| 'fontSize'
	| 'fontFamily'
	| 'fontWeight'
	| 'lineHeight'
	| 'zIndex'
	| 'duration'
	| 'easing';

interface TokenGroupProps {
	/** One or more custom property prefixes without the leading dashes, e.g. `spacing` or `['button-height', 'input-height']`. */
	prefix: string | string[];
	kind: TokenKind;
	/** `bar` only: draw the bar at this fraction of its real width, e.g. `0.25`. A caption says so. */
	scale?: number;
}

interface Token {
	name: string;
	value: string;
}

const SAMPLE_LATIN = 'The quick brown fox';
const SAMPLE_JAPANESE = '日本語を読む';

/** Resolves a token to the number the browser uses: `z-index` tokens are `calc()` chains of other tokens. */
const resolveZIndex = (name: string): number => {
	const probe = document.createElement('div');
	probe.style.position = 'absolute';
	probe.style.visibility = 'hidden';
	probe.style.zIndex = `var(${name})`;
	document.body.appendChild(probe);
	const resolved = Number.parseInt(getComputedStyle(probe).zIndex, 10);
	probe.remove();
	return Number.isNaN(resolved) ? 0 : resolved;
};

const isJapaneseToken = (name: string): boolean =>
	name.includes('japanese') || name.endsWith('-ja') || name.endsWith('-ja-serif');

const MotionRow: React.FC<{ token: Token; kind: 'duration' | 'easing' }> = ({ token, kind }) => {
	const [isPlayed, setIsPlayed] = useState(false);
	const style = {
		'--token-duration': kind === 'duration' ? `var(${token.name})` : 'var(--duration-lg)',
		'--token-easing': kind === 'easing' ? `var(${token.name})` : 'var(--ease-base)',
	} as React.CSSProperties;

	return (
		<div className={styles.row}>
			<strong className={styles.name}>{token.name}</strong>
			<code className={styles.value}>{token.value}</code>
			<div className={styles.preview}>
				<button
					type="button"
					className={styles.play}
					aria-label={`Play ${token.name}`}
					aria-pressed={isPlayed}
					onClick={() => setIsPlayed((played) => !played)}
				>
					Play
				</button>
				<div className={styles.track} style={style} data-played={isPlayed}>
					<span className={styles.dot} />
				</div>
			</div>
		</div>
	);
};

const renderPreview = (
	kind: TokenKind,
	token: Token,
	scale: number | undefined,
	zIndex: number,
	maxZIndex: number,
): React.ReactNode => {
	const reference = `var(${token.name})`;
	const lang = isJapaneseToken(token.name) ? 'ja' : undefined;

	switch (kind) {
		case 'bar':
			return (
				<span className={styles.bar} style={{ width: scale ? `calc(${reference} * ${scale})` : reference }} />
			);
		case 'square':
			return <span className={styles.square} style={{ width: reference, height: reference }} />;
		case 'radius':
			return <span className={styles.swatch} style={{ borderRadius: reference }} />;
		case 'border':
			return <span className={styles.swatch} style={{ borderWidth: reference }} />;
		case 'shadow':
			return <span className={styles.shadowSwatch} style={{ boxShadow: reference }} />;
		case 'opacity':
			return (
				<span className={styles.opacityBackdrop}>
					<span className={styles.opacitySwatch} style={{ opacity: reference }} />
				</span>
			);
		case 'aspectRatio':
			return <span className={styles.ratio} style={{ aspectRatio: reference }} />;
		case 'fontSize':
			return (
				<span style={{ fontSize: reference, lineHeight: 'var(--line-height-md)' }} lang={lang}>
					{lang ? SAMPLE_JAPANESE : SAMPLE_LATIN}
				</span>
			);
		case 'fontFamily':
			return (
				<span className={styles.fontSample} style={{ fontFamily: reference }} lang={lang}>
					Aa Bb 0123 日本語 よみ
				</span>
			);
		case 'fontWeight':
			return (
				<span className={styles.fontSample} style={{ fontWeight: reference }}>
					{SAMPLE_LATIN}
				</span>
			);
		case 'lineHeight':
			return (
				<span className={styles.lines} style={{ lineHeight: reference }}>
					Two lines of text set
					<br />
					with this line height
				</span>
			);
		case 'zIndex':
			return (
				<>
					<span
						className={styles.bar}
						style={{ width: `${(Math.max(zIndex, 0) / maxZIndex) * 40}%`, minWidth: 'var(--spacing-2xs)' }}
					/>
					<code className={styles.expression}>{token.value}</code>
				</>
			);
		default:
			return null;
	}
};

export const TokenGroup: React.FC<TokenGroupProps> = ({ prefix, kind, scale }) => {
	const key = Array.isArray(prefix) ? prefix.join('|') : prefix;

	const tokens = useMemo<Token[]>(
		() =>
			key
				.split('|')
				.flatMap((item) => Object.entries(getElemCSSVars(item)))
				.map(([name, value]) => ({ name, value: value.trim() })),
		[key],
	);

	const zIndexes = useMemo<Record<string, number>>(
		() =>
			kind === 'zIndex' ? Object.fromEntries(tokens.map((token) => [token.name, resolveZIndex(token.name)])) : {},
		[kind, tokens],
	);

	if (!tokens.length) {
		return <p className={styles.error}>No results for {`'${key}'`}.</p>;
	}

	const ordered = kind === 'zIndex' ? [...tokens].sort((a, b) => zIndexes[a.name] - zIndexes[b.name]) : tokens;
	const maxZIndex = Math.max(1, ...Object.values(zIndexes));

	return (
		<div className={styles.group}>
			{kind === 'bar' && scale !== undefined && scale !== 1 && (
				<p className={styles.caption}>Bars are drawn at {Math.round(scale * 100)}% of their real width.</p>
			)}
			{ordered.map((token) =>
				kind === 'duration' || kind === 'easing' ? (
					<MotionRow key={token.name} token={token} kind={kind} />
				) : (
					<div className={styles.row} key={token.name}>
						<strong className={styles.name}>{token.name}</strong>
						<code className={styles.value}>{kind === 'zIndex' ? zIndexes[token.name] : token.value}</code>
						<div className={styles.preview}>
							{renderPreview(kind, token, scale, zIndexes[token.name], maxZIndex)}
						</div>
					</div>
				),
			)}
		</div>
	);
};
