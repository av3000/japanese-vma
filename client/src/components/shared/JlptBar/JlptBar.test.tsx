import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import type { ArticleResourceJlptLevels } from '@/api/generated/model/articleResourceJlptLevels';
import { dominantJlptLevel, JlptBar, toJlptSegments } from './index';

const levels = (overrides: Partial<ArticleResourceJlptLevels> = {}): ArticleResourceJlptLevels => ({
	n1: 0,
	n2: 0,
	n3: 0,
	n4: 0,
	n5: 0,
	uncommon: 0,
	...overrides,
});

describe('toJlptSegments', () => {
	it('orders segments N5 to N1 with uncommon last', () => {
		const segments = toJlptSegments(levels({ n1: 1, n2: 2, n3: 3, n4: 4, n5: 5, uncommon: 6 }));

		expect(segments.map((segment) => segment.label)).toEqual(['N5', 'N4', 'N3', 'N2', 'N1', 'Uncommon']);
		expect(segments.map((segment) => segment.count)).toEqual([5, 4, 3, 2, 1, 6]);
	});

	it('omits zero-count levels', () => {
		const segments = toJlptSegments(levels({ n5: 12, n3: 15, uncommon: 0 }));

		expect(segments.map((segment) => segment.level)).toEqual(['N5', 'N3']);
	});

	it('marks only the dominant level, never uncommon', () => {
		const segments = toJlptSegments(levels({ n5: 12, n4: 8, n3: 15, uncommon: 40 }));

		expect(segments.filter((segment) => segment.isDominant).map((segment) => segment.level)).toEqual(['N3']);
	});

	it('returns no segments when every count is 0', () => {
		expect(toJlptSegments(levels())).toEqual([]);
	});
});

describe('dominantJlptLevel', () => {
	it('picks the level with the most kanji', () => {
		expect(dominantJlptLevel(levels({ n5: 12, n4: 8, n3: 15 }))).toBe('N3');
	});

	it('gives a tie to the harder level', () => {
		expect(dominantJlptLevel(levels({ n5: 10, n2: 10, n4: 3 }))).toBe('N2');
	});

	it('returns null when no JLPT level has kanji, even with uncommon ones', () => {
		expect(dominantJlptLevel(levels())).toBeNull();
		expect(dominantJlptLevel(levels({ uncommon: 9 }))).toBeNull();
	});
});

describe('JlptBar', () => {
	it('renders nothing when every count is 0', () => {
		expect(renderToStaticMarkup(<JlptBar levels={levels()} />)).toBe('');
	});

	it('names the bar with the dominant level and every count', () => {
		const html = renderToStaticMarkup(<JlptBar levels={levels({ n5: 12, n4: 8, n3: 15, uncommon: 2 })} />);

		expect(html).toContain('role="img"');
		expect(html).toContain('aria-label="Mostly N3: N5 12, N4 8, N3 15, uncommon 2"');
	});

	it('names an uncommon-only bar without claiming a level', () => {
		const html = renderToStaticMarkup(<JlptBar levels={levels({ uncommon: 4 })} />);

		expect(html).toContain('aria-label="Kanji by JLPT level: uncommon 4"');
	});

	it('hides segments and printed counts from assistive technology, in display order', () => {
		const html = renderToStaticMarkup(<JlptBar levels={levels({ n1: 3, n5: 7, uncommon: 1 })} />);

		expect(html.match(/data-level="([^"]+)"/g)).toEqual([
			'data-level="N5"',
			'data-level="N1"',
			'data-level="uncommon"',
		]);
		expect(html.match(/aria-hidden="true"/g)).toHaveLength(6);
		expect(html).toMatch(/>7<.*>3<.*>1</);
	});
});
