import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { defaultStudyConfig } from '@/api/flashcards/deck';
import { SavedListType } from '@/shared/constants/enums';
import { StudySetupForm, type StudyDeckStatus } from './index';

const ready: StudyDeckStatus = { kind: 'ready', totalItems: 12, eligibleItems: 10, excludedEmptyAnswerField: 2 };

const render = (props: Partial<Parameters<typeof StudySetupForm>[0]> = {}) =>
	renderToStaticMarkup(
		<StudySetupForm
			catalogueType={SavedListType.KANJIS}
			value={defaultStudyConfig()}
			onChange={vi.fn()}
			onStart={vi.fn()}
			deckStatus={ready}
			{...props}
		/>,
	);

describe('StudySetupForm', () => {
	it('offers the kanji fields with kanji wording', () => {
		const html = render();

		expect(html).toContain('>Kanji<');
		expect(html).toContain('Meaning (English)');
		expect(html).toContain('On’yomi (katakana)');
		expect(html).toContain('Kun’yomi (hiragana)');
		expect(html).not.toContain('Reading (kana)');
	});

	it('offers word fields for a words catalogue', () => {
		const html = render({ catalogueType: SavedListType.KNOWNWORDS });

		expect(html).toContain('>Word<');
		expect(html).toContain('Reading (kana)');
		expect(html).not.toContain('On’yomi');
	});

	it('does not offer typed mode when the answer is the character', () => {
		const html = render({ value: { ...defaultStudyConfig(), prompt: 'meaning', answer: 'character' } });

		expect(html).toContain('Pick one of four');
		expect(html).not.toContain('Type the answer');
		expect(html).toContain('always picked from options');
	});

	it('offers typed mode and the script checkbox for a kun’yomi answer', () => {
		const html = render({ value: { ...defaultStudyConfig(), answer: 'kunyomi', mode: 'typed' } });

		expect(html).toContain('Type the answer');
		expect(html).toContain('Accept katakana too');
	});

	it('reports how many items can be asked and how many are skipped', () => {
		const html = render();

		expect(html).toContain('<strong>10</strong> of 12 items can be asked this way');
		expect(html).toContain('2 have nothing to answer with and will be skipped');
	});

	it('shows the backend refusal as an alert and disables Start', () => {
		const html = render({
			deckStatus: { kind: 'error', title: 'No eligible cards', detail: 'No item has a kunyomi' },
		});

		expect(html).toContain('No eligible cards');
		expect(html).toContain('No item has a kunyomi');
		expect(html).toMatch(/<button[^>]*disabled[^>]*>[^<]*Start studying/);
	});

	it('refuses a catalogue type that cannot be studied', () => {
		const html = render({ catalogueType: SavedListType.SENTENCES });

		expect(html).toContain('This catalogue cannot be studied');
		expect(html).not.toContain('Start studying');
	});
});
