import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import { articleStatusPill, processingStatusPill, STATUS_TONES, StatusPill } from './';

describe('articleStatusPill', () => {
	it.each([
		[ARTICLE_STATUS.PENDING, 'Approval: Pending', 'warning'],
		[ARTICLE_STATUS.PROCESSED, 'Approval: Processed', 'neutral'],
		[ARTICLE_STATUS.REVIEWING, 'Approval: Reviewing', 'info'],
		[ARTICLE_STATUS.REJECTED, 'Approval: Rejected', 'danger'],
		[ARTICLE_STATUS.APPROVED, 'Approval: Approved', 'success'],
	])('maps status %s to "%s" in the %s tone', (status, label, tone) => {
		expect(articleStatusPill(status)).toEqual({ tone, label });
	});

	it('falls back to the pending pill for a value outside the enum', () => {
		expect(articleStatusPill(99)).toEqual({ tone: 'warning', label: 'Approval: Pending' });
	});
});

describe('processingStatusPill', () => {
	it.each([
		[ProcessingStatus.pending, 'Pending', 'warning', 'minusSolid'],
		[ProcessingStatus.processing, 'Processing', 'warning', 'spinner'],
		[ProcessingStatus.completed, 'Completed', 'success', 'checkSolid'],
		[ProcessingStatus.failed, 'Failed', 'danger', 'removeSolid'],
		[ProcessingStatus.superseded, 'Superseded', 'neutral', 'minusSolid'],
	])('maps %s to "%s" in the %s tone with the %s icon', (status, label, tone, icon) => {
		expect(processingStatusPill(status)).toEqual({ tone, label, icon });
	});

	it('only ever uses tones StatusPill knows about', () => {
		for (const status of Object.values(ProcessingStatus)) {
			expect(STATUS_TONES).toContain(processingStatusPill(status).tone);
		}
	});
});

describe('StatusPill', () => {
	it('renders its label as visible text, not as an attribute', () => {
		const html = renderToStaticMarkup(<StatusPill tone="success" label="Approval: Approved" />);

		expect(html).toContain('>Approval: Approved</span>');
		expect(html).not.toContain('aria-hidden="true">Approval');
	});

	it.each(STATUS_TONES)('renders the %s tone with a leading icon', (tone) => {
		const html = renderToStaticMarkup(<StatusPill tone={tone} label="Label" />);

		expect(html).toContain('<svg');
		expect(html).not.toContain('data-icon="spinner"');
	});

	it('renders a spinner only for the processing status', () => {
		const spinnerFor = (status: ProcessingStatus) =>
			renderToStaticMarkup(<StatusPill {...processingStatusPill(status)} />).includes('data-icon="spinner"');

		expect(spinnerFor(ProcessingStatus.processing)).toBe(true);
		expect(
			[
				ProcessingStatus.pending,
				ProcessingStatus.completed,
				ProcessingStatus.failed,
				ProcessingStatus.superseded,
			].some(spinnerFor),
		).toBe(false);
	});

	it('hides the icon from assistive technology so only the label is announced', () => {
		const html = renderToStaticMarkup(<StatusPill tone="info" label="Approval: Reviewing" />);

		expect(html).toMatch(/<span class="[^"]*" aria-hidden="true" data-icon="eyeRegular">/);
	});
});
