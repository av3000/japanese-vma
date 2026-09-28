import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { CorpusStatsResource } from '@/api/generated/model';
import { CorpusStatsTiles } from './index';

const query = vi.hoisted(() => ({
	value: { status: 'pending', data: undefined } as { status: string; data: unknown },
}));

vi.mock('@/api/generated/corpus-stats/corpus-stats', () => ({
	useCorpusStatsShow: () => query.value,
}));

const stats: CorpusStatsResource = { radicals: 214, kanjis: 13108, words: 183512, sentences: 149862 };

const render = () =>
	renderToStaticMarkup(
		<MemoryRouter>
			<CorpusStatsTiles />
		</MemoryRouter>,
	);

describe('CorpusStatsTiles', () => {
	beforeEach(() => {
		query.value = { status: 'pending', data: undefined };
	});

	it('links each tile to its list with a thousands-separated count', () => {
		query.value = { status: 'success', data: stats };
		const html = render();

		const tiles = Array.from(
			html.matchAll(/<a[^>]*href="([^"]+)"[^>]*><span[^>]*>([^<]+)<\/span> <span[^>]*>([^<]+)<\/span><\/a>/g),
		).map(([, href, count, label]) => ({ href, count, label }));
		expect(tiles).toEqual([
			{ href: '/radicals', count: '214', label: 'Radicals' },
			{ href: '/kanjis', count: '13,108', label: 'Kanji' },
			{ href: '/words', count: '183,512', label: 'Words' },
			{ href: '/sentences', count: '149,862', label: 'Sentences' },
		]);
	});

	it('gives each tile a spaced accessible name, e.g. "13,108 Kanji"', () => {
		query.value = { status: 'success', data: stats };

		expect(render()).toMatch(/>13,108<\/span> <span[^>]*>Kanji</);
	});

	it('shows four skeleton tiles, hidden from assistive technology, while loading', () => {
		const html = render();

		expect(html.match(/data-testid="corpus-tile-skeleton"/g)).toHaveLength(4);
		expect(html).toContain('aria-hidden="true"');
		expect(html).not.toContain('<a');
	});

	it('renders nothing on error', () => {
		query.value = { status: 'error', data: undefined };

		expect(render()).toBe('');
	});
});
