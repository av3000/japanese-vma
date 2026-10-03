import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import { kanjiCards } from '../fixtures';
import { SessionSummary, type SessionAnswer, type SessionSummaryProps } from './index';

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useLocation: () => ({ pathname: '/catalogues/c-1/study', search: '?play=1', hash: '', state: null, key: 'k' }),
	};
});

vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: ({ entityId, instanceObjectType }: { entityId: number; instanceObjectType: number }) => (
		<span>
			bookmark:{entityId}:{instanceObjectType}
		</span>
	),
}));

const answer = (index: number, correct: boolean): SessionAnswer => ({
	card: kanjiCards[index],
	given: correct ? kanjiCards[index].displayAnswer : 'fire',
	correct,
	matched: correct ? kanjiCards[index].displayAnswer : null,
	responseMs: 1800,
});

const render = (props: Partial<SessionSummaryProps> = {}) =>
	renderToStaticMarkup(
		<SessionSummary
			answers={[answer(0, true), answer(1, false), answer(2, true)]}
			attemptNo={1}
			promptJapanese
			answerJapanese={false}
			catalogueTitle="N5 kanji"
			catalogueHref="/catalogues/c-1"
			saveStatus="completed"
			bookmarkCatalogueType={SavedListType.KANJIS}
			onRetryMissed={vi.fn()}
			onRestart={vi.fn()}
			onChangeSetup={vi.fn()}
			{...props}
		/>,
	);

describe('SessionSummary', () => {
	it('shows the score, the missed card with its answers and a bookmark into a kanji catalogue', () => {
		const html = render();

		expect(html).toContain('2 of 3 correct');
		expect(html).toContain('1 card to look at again');
		expect(html).toContain('水');
		expect(html).toContain('you said: <span>fire</span>');
		expect(html).toContain(`bookmark:${kanjiCards[1].itemId}:${SavedListType.KANJIS}`);
		expect(html).toContain('Retry missed (1)');
		expect(html).toContain('Results saved.');
	});

	it('tells a visitor to sign in and offers no bookmarks', () => {
		const html = render({ saveStatus: 'disabled' });

		expect(html).toContain('to save your progress');
		expect(html).toContain('href="/login"');
		expect(html).not.toContain('bookmark:');
	});

	it('says when saving failed', () => {
		expect(render({ saveStatus: 'failed' })).toContain('could not be saved');
	});

	it('hides Retry missed and the list when every card was right', () => {
		const html = render({ answers: [answer(0, true), answer(1, true), answer(2, true)] });

		expect(html).not.toContain('Retry missed');
		expect(html).not.toContain('Missed cards');
		expect(html).toContain('Every card right');
	});

	it('labels a retry round', () => {
		expect(render({ attemptNo: 2, answers: [answer(1, true)] })).toContain('retry round 1');
	});
});
