/**
 * @vitest-environment jsdom
 */
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import { renderWithAct } from '@/test/renderWithAct';
import ProcessingStatusAlert, { READY_ANNOUNCEMENT } from './index';

vi.mock('@/providers/contexts/socket-provider', () => ({
	useWebSocket: vi.fn(),
}));

const status = (value: ProcessingStatus, attempt = 1): ProcessingStatusResource => ({
	id: 1,
	entity_id: 'entity-uuid',
	attempt,
	max_attempts: 3,
	sequence: 1,
	type: 'article_content_processing',
	status: value,
	metadata: {},
	created_at: '2026-09-20T10:00:00+00:00',
	updated_at: '2026-09-20T10:00:05+00:00',
});

const connect = (isConnected: boolean) =>
	vi.mocked(useWebSocket).mockReturnValue({
		isConnected,
		connectionStatus: isConnected ? 'connected' : 'disconnected',
	} as never);

const render = (value: ProcessingStatus, { isConnected = false, attempt = 1, isOwner = false } = {}) => {
	connect(isConnected);
	return renderToStaticMarkup(<ProcessingStatusAlert processing_status={status(value, attempt)} isOwner={isOwner} />);
};

/** Text of the always-present live region, and whether anything else rendered. */
const visibleText = (html: string) => html.replace(/<p[^>]*role="status"[^>]*>[^<]*<\/p>$/, '');

describe('ProcessingStatusAlert', () => {
	it.each([
		[ProcessingStatus.pending, 'queued'],
		[ProcessingStatus.processing, 'Extracting kanji and vocabulary'],
	])('renders %s with article-specific copy', (value, fragment) => {
		expect(render(value)).toContain(fragment);
	});

	it.each([
		[ProcessingStatus.pending, 'Pending'],
		[ProcessingStatus.processing, 'Processing'],
		[ProcessingStatus.failed, 'Failed'],
	])('shows %s as a status pill labelled "%s"', (value, label) => {
		expect(render(value)).toContain(`>${label}</span>`);
	});

	it.each([ProcessingStatus.completed, ProcessingStatus.superseded])('is silent for %s', (value) => {
		const html = render(value);

		expect(visibleText(html)).toBe('');
		expect(html).not.toContain(READY_ANNOUNCEMENT);
	});

	it('renders only the empty live region without a status', () => {
		connect(false);
		const html = renderToStaticMarkup(<ProcessingStatusAlert processing_status={null} />);

		expect(html).toMatch(/^<p[^>]*role="status"[^>]*><\/p>$/);
	});

	it('tells the owner how to run a failed analysis again', () => {
		const html = render(ProcessingStatus.failed, { isOwner: true });

		expect(html).toContain('Saving a change to the Japanese title or text runs it again.');
	});

	it('tells everyone else only that the lists are not available', () => {
		const html = render(ProcessingStatus.failed);

		expect(html).toContain('available for this article yet.');
		expect(html).not.toContain('Saving a change');
	});

	it('announces politely, even when failed', () => {
		expect(render(ProcessingStatus.failed)).not.toContain('role="alert"');
	});

	it('keeps the timing details in a native disclosure', () => {
		const html = render(ProcessingStatus.failed, { attempt: 3 });

		expect(html).toContain('<details');
		expect(html).toContain('<summary');
		expect(html).toContain('Duration');
		expect(html).toContain('Attempt 3 of 3');
	});

	it('spins inside the pill for processing only', () => {
		expect(render(ProcessingStatus.processing).match(/data-icon="spinner"/g)).toHaveLength(1);
		expect(render(ProcessingStatus.pending)).not.toContain('data-icon="spinner"');
	});

	it('shows retry progress only once processing is past its first attempt', () => {
		expect(render(ProcessingStatus.processing, { attempt: 1 })).not.toContain('Attempt');
		expect(render(ProcessingStatus.processing, { attempt: 2 })).toContain('Attempt 2 of 3');
	});

	it('promises live updates only while the socket is connected', () => {
		expect(render(ProcessingStatus.processing, { isConnected: true })).toContain('update automatically');
		expect(render(ProcessingStatus.processing)).toContain('Checking for updates');
	});

	it('announces completion when processing finishes while the page is open', async () => {
		connect(true);
		const view = await renderWithAct(
			<ProcessingStatusAlert processing_status={status(ProcessingStatus.processing)} />,
		);

		expect(view.container.textContent).toContain('Extracting kanji');

		await view.rerender(<ProcessingStatusAlert processing_status={status(ProcessingStatus.completed)} />);

		expect(view.container.textContent).not.toContain('Extracting kanji');
		expect(view.container.querySelector('[role="status"]')?.textContent).toBe(READY_ANNOUNCEMENT);
		await view.unmount();
	});

	it('does not announce an article that loads already completed', async () => {
		connect(true);
		const view = await renderWithAct(
			<ProcessingStatusAlert processing_status={status(ProcessingStatus.completed)} />,
		);

		expect(view.container.textContent).toBe('');
		await view.unmount();
	});
});
