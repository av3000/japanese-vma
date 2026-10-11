import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import type { ModalController } from '@/hooks/useModal';
import { RelatedListModal, RelatedPreview, type RelatedPreviewProps } from './RelatedPreview';

const controller = (overrides: Partial<ModalController> = {}): ModalController => ({
	id: 'kanji-words-modal',
	dialogRef: { current: null },
	isOpen: false,
	isRendered: false,
	open: vi.fn(),
	close: vi.fn(),
	...overrides,
});

const render = (props: Partial<RelatedPreviewProps> = {}) =>
	renderToStaticMarkup(
		<RelatedPreview
			title="Words with 人"
			noun="words"
			total={2398}
			isPending={false}
			isError={false}
			isEmpty={false}
			emptyMessage="No words use this kanji yet."
			controller={controller()}
			{...props}
		>
			<ul>
				<li>人間</li>
			</ul>
		</RelatedPreview>,
	);

describe('RelatedPreview', () => {
	it('shows the heading with its count, the rows and a See all button wired to the modal', () => {
		const html = render();

		expect(html).toMatch(/<h2 [^>]*>Words with 人<span[^>]*>2,398<\/span><\/h2>/);
		expect(html).toContain('<li>人間</li>');
		expect(html).toContain('aria-controls="kanji-words-modal"');
		expect(html).toContain('aria-expanded="false"');
		expect(html).toContain('See all 2,398 words');
	});

	it('takes the heading level it is given', () => {
		expect(render({ headingLevel: 3 })).toMatch(/<h3 [^>]*>Words with 人/);
	});

	it('shows one line while loading, with no count and no rows', () => {
		const html = render({ isPending: true });

		expect(html).toContain('Loading words…');
		expect(html).not.toContain('2,398');
		expect(html).not.toContain('<li>');
		expect(html).not.toContain('See all');
	});

	it('shows a fixed line on error', () => {
		const html = render({ isError: true });

		expect(html).toContain('role="status"');
		expect(html).toContain('Words with 人 could not be loaded. Reload the page to try again.');
		expect(html).not.toContain('See all');
	});

	it('shows the empty line and no button when there is nothing', () => {
		const html = render({ isEmpty: true, total: 0 });

		expect(html).toContain('No words use this kanji yet.');
		expect(html).not.toContain('See all');
	});
});

describe('RelatedListModal', () => {
	it('renders nothing until the modal has been opened', () => {
		const html = renderToStaticMarkup(
			<RelatedListModal controller={controller()} title="Words with 人">
				<p>table</p>
			</RelatedListModal>,
		);

		expect(html).toBe('');
	});
});
