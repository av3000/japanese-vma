// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { ArticleReviewModal } from '@/components/features/articles/ArticleReviewModal';
import CommentForm from '@/components/features/comment/CommentsBlock/CommentForm/CommentForm';
import type { ModalController } from '@/hooks/useModal';
import { createTranslate } from '@/i18n/translate';
import { TranslationContext, type TranslationContextValue } from '@/providers/contexts/translation-provider';
import { renderWithAct } from '@/test/renderWithAct';

/** The shared Cancel buttons read `common.cancel`, and a caller's own `cancelLabel` still wins. */

const controller: ModalController = {
	id: 'modal',
	dialogRef: { current: null },
	isOpen: true,
	isRendered: true,
	open: vi.fn(),
	close: vi.fn(),
};

const context = (messages: Record<string, string>): TranslationContextValue => ({
	t: createTranslate(messages),
	locale: 'da',
	displayedLocale: 'da',
	locales: [{ code: 'en', name: 'English' }],
	setLocale: vi.fn(),
	error: null,
});

const danish = context({ 'common.cancel': 'Annuller' });

const cases: [string, (cancelLabel?: string) => React.ReactElement][] = [
	[
		'DeleteInstanceModal',
		(cancelLabel) => (
			<DeleteInstanceModal
				controller={controller}
				instanceName="Article"
				onDelete={vi.fn()}
				isProcessing={false}
				cancelLabel={cancelLabel}
			/>
		),
	],
	[
		'ArticleReviewModal',
		(cancelLabel) => (
			<ArticleReviewModal
				controller={controller}
				status={0}
				onStatusChange={vi.fn()}
				onSave={vi.fn()}
				isProcessing={false}
				cancelLabel={cancelLabel}
			/>
		),
	],
];

const buttonLabels = (container: HTMLElement) => Array.from(container.querySelectorAll('button'), (b) => b.textContent);

afterEach(() => {
	document.body.innerHTML = '';
});

describe('the shared Cancel buttons', () => {
	it.each(cases)(
		'%s shows the translated Cancel, English when untranslated, and a caller label over both',
		async (_, render) => {
			const translated = await renderWithAct(
				<TranslationContext.Provider value={danish}>{render()}</TranslationContext.Provider>,
			);
			expect(buttonLabels(document.body)).toContain('Annuller');
			await translated.unmount();

			const english = await renderWithAct(<>{render()}</>);
			expect(buttonLabels(document.body)).toContain('Cancel');
			await english.unmount();

			const custom = await renderWithAct(
				<TranslationContext.Provider value={danish}>{render('Keep it')}</TranslationContext.Provider>,
			);
			expect(buttonLabels(document.body)).toContain('Keep it');
			expect(buttonLabels(document.body)).not.toContain('Annuller');
			await custom.unmount();
		},
	);

	it('CommentForm shows the translated Cancel, and English without a provider', async () => {
		const mountForm = () => <CommentForm onSubmit={vi.fn()} isSubmitting={false} onCancel={vi.fn()} />;

		const translated = await renderWithAct(
			<TranslationContext.Provider value={danish}>{mountForm()}</TranslationContext.Provider>,
		);
		expect(buttonLabels(translated.container)).toContain('Annuller');
		await translated.unmount();

		const english = await renderWithAct(mountForm());
		expect(buttonLabels(english.container)).toContain('Cancel');
		await english.unmount();
	});
});
