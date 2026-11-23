import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { kanjiCards } from '../fixtures';
import { AnswerFeedback } from './index';

describe('AnswerFeedback', () => {
	it('names the verdict in words, lists every accepted answer and marks the matched one', () => {
		const html = renderToStaticMarkup(
			<AnswerFeedback
				card={kanjiCards[0]}
				correct
				matched="learning"
				given="learning"
				japanese={false}
				isLast={false}
				onNext={vi.fn()}
			/>,
		);

		expect(html).toContain('Correct');
		expect(html).toContain('Accepted answers');
		for (const answer of ['study', 'learning', 'science']) {
			expect(html).toContain(`>${answer}</li>`);
		}
		expect(html).toMatch(/_matched_[a-z0-9]+"[^>]*>learning</);
		expect(html).toContain('Next card');
	});

	it('shows what the learner answered when wrong and offers the results on the last card', () => {
		const html = renderToStaticMarkup(
			<AnswerFeedback
				card={kanjiCards[1]}
				correct={false}
				matched={null}
				given="fire"
				japanese={false}
				isLast
				onNext={vi.fn()}
			/>,
		);

		expect(html).toContain('Not quite');
		expect(html).toContain('you answered');
		expect(html).toContain('>fire<');
		expect(html).toContain('>Answer</p>');
		expect(html).toContain('>water</li>');
		expect(html).toContain('See results');
	});

	it('keeps the okurigana dot visible in a kun’yomi answer', () => {
		const card = { ...kanjiCards[0], acceptedAnswers: ['まな.ぶ'] };
		const html = renderToStaticMarkup(
			<AnswerFeedback
				card={card}
				correct
				matched="まな.ぶ"
				given="まなぶ"
				japanese
				isLast={false}
				onNext={vi.fn()}
			/>,
		);

		expect(html).toContain('lang="ja"');
		expect(html).toContain('>まな.ぶ</li>');
	});
});
