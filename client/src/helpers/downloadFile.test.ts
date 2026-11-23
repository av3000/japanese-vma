import { afterEach, describe, expect, it, vi } from 'vitest';
import { downloadFile, toDownloadFileName } from './downloadFile';

describe('toDownloadFileName', () => {
	it('keeps a readable title, including Japanese, as the filename stem', () => {
		expect(toDownloadFileName('日本語の漢字', 'catalogue-kanjis', 'pdf')).toBe('日本語の漢字.pdf');
	});

	it('strips the characters a filesystem or a browser would choke on', () => {
		expect(toDownloadFileName('JLPT N5 / N4: kanji?', 'catalogue-kanjis', 'pdf')).toBe('JLPT N5 N4 kanji.pdf');
		expect(toDownloadFileName('line\nbreak\ttab', 'catalogue-kanjis', 'pdf')).toBe('line break tab.pdf');
	});

	it('falls back to the generic stem when nothing usable survives sanitising', () => {
		expect(toDownloadFileName('   ', 'catalogue-radicals', 'pdf')).toBe('catalogue-radicals.pdf');
		expect(toDownloadFileName('///', 'catalogue-radicals', 'pdf')).toBe('catalogue-radicals.pdf');
	});

	it('caps a long title so the name stays usable', () => {
		const name = toDownloadFileName('あ'.repeat(200), 'catalogue-words', 'pdf');

		expect(name).toHaveLength(60 + '.pdf'.length);
	});
});

describe('downloadFile', () => {
	afterEach(() => {
		vi.unstubAllGlobals();
	});

	it('downloads under the given name and releases the object url', () => {
		const createObjectURL = vi.fn().mockReturnValue('blob:pdf');
		const revokeObjectURL = vi.fn();
		const click = vi.fn();
		const remove = vi.fn();
		const anchor: Record<string, unknown> = { click, remove };

		vi.stubGlobal('URL', { createObjectURL, revokeObjectURL });
		vi.stubGlobal('document', {
			createElement: vi.fn().mockReturnValue(anchor),
			body: { appendChild: vi.fn() },
		});

		const blob = { type: 'application/pdf' } as Blob;
		downloadFile('catalogue-radicals.pdf', blob);

		expect(createObjectURL).toHaveBeenCalledWith(blob);
		expect(anchor.href).toBe('blob:pdf');
		expect(anchor.download).toBe('catalogue-radicals.pdf');
		expect(click).toHaveBeenCalledOnce();
		expect(remove).toHaveBeenCalledOnce();
		expect(revokeObjectURL).toHaveBeenCalledWith('blob:pdf');
	});

	it('releases the object url even when the click throws', () => {
		const revokeObjectURL = vi.fn();

		vi.stubGlobal('URL', { createObjectURL: vi.fn().mockReturnValue('blob:pdf'), revokeObjectURL });
		vi.stubGlobal('document', {
			createElement: vi.fn().mockReturnValue({
				click: vi.fn().mockImplementation(() => {
					throw new Error('blocked');
				}),
				remove: vi.fn(),
			}),
			body: { appendChild: vi.fn() },
		});

		expect(() => downloadFile('catalogue-words.pdf', {} as Blob)).toThrow('blocked');
		expect(revokeObjectURL).toHaveBeenCalledWith('blob:pdf');
	});
});
