import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import type { CatalogueArticleItem } from '@/api/catalogues/catalogues';
import { ObjectTemplates } from '@/shared/constants';
import type { User } from '@/types';
import { CatalogueItems } from './index';

const capturedButtons: Array<{ ariaLabel?: string; onClick?: () => void }> = [];

const removeButtons = () => capturedButtons.filter((button) => button.ariaLabel?.startsWith('Remove '));

vi.mock('@/components/shared/Button', () => ({
	Button: ({
		children,
		onClick,
		'aria-label': ariaLabel,
	}: {
		children?: React.ReactNode;
		onClick?: () => void;
		'aria-label'?: string;
	}) => {
		capturedButtons.push({ ariaLabel, onClick });
		return (
			<button type="button" aria-label={ariaLabel}>
				{children}
			</button>
		);
	},
}));

vi.mock('@/components/shared/Icon', () => ({
	Icon: ({ name }: { name: string }) => <i data-icon={name} />,
}));

const owner = { id: 7 } as User;

const articleItem = {
	id: 41,
	uuid: 'e2f6d1c0-1111-4222-8333-444455556666',
	title_jp: '日本語の記事',
	saves_count: 3,
	hashtags: [{ id: 1, content: 'grammar' }],
	engagement: {
		views_count: 12,
		downloads_count: 1,
		comments_count: 2,
		likes_count: 5,
	},
} as unknown as CatalogueArticleItem;

const kanjiItem = { id: 88, kanji: '語', onyomi: 'ゴ', kunyomi: 'かた|る', meaning: 'language|word', jlpt: '3', frequency: 301 };
const radicalItem = { id: 12, radical: '氵', strokes: 3, meaning: 'water', hiragana: 'みず' };
const wordItem = { id: 55, word: '言葉', furigana: 'ことば', meaning: 'word', jlpt: '3', word_type: 'noun' };
const sentenceItem = { id: 63, content: 'これは文です。', tatoeba_entry: 9876 };

const renderItems = (props: Partial<Parameters<typeof CatalogueItems>[0]> = {}) => {
	capturedButtons.length = 0;
	return renderToStaticMarkup(
		<MemoryRouter>
			<CatalogueItems
				items={[]}
				catalogueType={ObjectTemplates.ARTICLES}
				currentUser={owner}
				ownerId={owner.id}
				editMode={false}
				onRemoveItem={() => {}}
				{...props}
			/>
		</MemoryRouter>,
	);
};

describe('CatalogueItems', () => {
	it('renders article items with their UUID detail link', () => {
		const html = renderItems({ catalogueType: ObjectTemplates.ARTICLES, items: [articleItem] });

		expect(html).toContain(`href="/articles/${articleItem.uuid}"`);
		expect(html).toContain('日本語の記事');
		expect(html).not.toContain(`/articles/${articleItem.id}`);
	});

	it.each([
		['kanji', ObjectTemplates.KANJIS, ObjectTemplates.KNOWNKANJIS, kanjiItem, '/kanji/88'],
		['radical', ObjectTemplates.RADICALS, ObjectTemplates.KNOWNRADICALS, radicalItem, '/radical/12'],
		['word', ObjectTemplates.WORDS, ObjectTemplates.KNOWNWORDS, wordItem, '/word/55'],
	])('renders %s items with their id detail link for both the plain and known type', (_label, type, knownType, item, href) => {
		expect(renderItems({ catalogueType: type, items: [item] })).toContain(`href="${href}"`);
		expect(renderItems({ catalogueType: knownType, items: [item] })).toContain(`href="${href}"`);
	});

	it('renders sentence items with their Tatoeba source link', () => {
		const html = renderItems({ catalogueType: ObjectTemplates.SENTENCES, items: [sentenceItem] });

		expect(html).toContain('これは文です。');
		expect(html).toContain('https://tatoeba.org/eng/sentences/show/9876');
	});

	it('renders each empty state for its own catalogue type', () => {
		expect(renderItems({ catalogueType: ObjectTemplates.ARTICLES })).toContain('No saved articles found.');
		expect(renderItems({ catalogueType: ObjectTemplates.KANJIS })).toContain('No saved kanji found.');
	});

	it('falls back to a placeholder for an unmapped catalogue type', () => {
		expect(renderItems({ catalogueType: 999 })).toContain('Unknown catalogue type');
	});

	it('forwards the removal of an owned article as a numeric id', () => {
		const onRemoveItem = vi.fn();
		renderItems({
			catalogueType: ObjectTemplates.ARTICLES,
			items: [articleItem],
			editMode: true,
			onRemoveItem,
		});

		const buttons = removeButtons();
		expect(buttons).toHaveLength(1);
		buttons[0].onClick?.();
		expect(onRemoveItem).toHaveBeenCalledWith(41);
	});

	it.each([
		['kanji', ObjectTemplates.KANJIS, kanjiItem],
		['radical', ObjectTemplates.RADICALS, radicalItem],
		['word', ObjectTemplates.WORDS, wordItem],
		['sentence', ObjectTemplates.SENTENCES, sentenceItem],
	])('defers %s removal to the confirmation dialog instead of removing straight away', (_label, type, item) => {
		const onRemoveItem = vi.fn();
		renderItems({ catalogueType: type, items: [item], editMode: true, onRemoveItem });

		const buttons = removeButtons();
		expect(buttons).toHaveLength(1);
		buttons[0].onClick?.();
		expect(onRemoveItem).not.toHaveBeenCalled();
	});

	it.each([
		['outside edit mode', { editMode: false }],
		['for non-owners', { editMode: true, currentUser: { id: 99 } as User }],
	])('hides removal controls %s', (_label, overrides) => {
		renderItems({ catalogueType: ObjectTemplates.ARTICLES, items: [articleItem], ...overrides });
		expect(removeButtons()).toHaveLength(0);

		renderItems({ catalogueType: ObjectTemplates.KANJIS, items: [kanjiItem], ...overrides });
		expect(removeButtons()).toHaveLength(0);
	});
});
