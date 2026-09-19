import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { LastOperationStatus } from '@/api/generated/model/lastOperationStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import ProcessingStatusAlert from './index';

vi.mock('@/providers/contexts/socket-provider', () => ({
	useWebSocket: vi.fn(),
}));

vi.mock('@/components/ui/popover', () => ({
	Popover: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
	PopoverTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
	PopoverContent: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
	PopoverHeader: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
	PopoverTitle: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
	PopoverDescription: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}));

const status = (value: LastOperationStatus): ProcessingStatusResource => ({
	id: 1,
	entity_id: 'entity-uuid',
	attempt: 1,
	type: 'article_content_processing',
	status: value,
	metadata: {},
	created_at: '2026-09-20T10:00:00+00:00',
	updated_at: '2026-09-20T10:00:05+00:00',
});

const render = (value: LastOperationStatus, isConnected = false) => {
	vi.mocked(useWebSocket).mockReturnValue({
		isConnected,
		connectionStatus: isConnected ? 'connected' : 'disconnected',
	} as never);
	return renderToStaticMarkup(<ProcessingStatusAlert processing_status={status(value)} />);
};

describe('ProcessingStatusAlert', () => {
	it.each([
		[LastOperationStatus.pending, 'queued'],
		[LastOperationStatus.processing, 'Extracting kanji and vocabulary'],
		[LastOperationStatus.completed, 'are ready'],
		[LastOperationStatus.failed, 'extraction failed'],
	])('renders %s with article-specific copy', (value, fragment) => {
		const html = render(value);

		expect(html).toContain(fragment);
		expect(html).not.toContain('Instance');
	});

	it('renders nothing for superseded, which is terminal and carries no result', () => {
		expect(render(LastOperationStatus.superseded)).toBe('');
	});

	it('renders nothing without a status', () => {
		vi.mocked(useWebSocket).mockReturnValue({ isConnected: false, connectionStatus: 'disconnected' } as never);
		expect(renderToStaticMarkup(<ProcessingStatusAlert processing_status={null} />)).toBe('');
	});

	it('promises live updates only while the socket is connected', () => {
		expect(render(LastOperationStatus.processing, true)).toContain('update automatically');
		expect(render(LastOperationStatus.processing, false)).toContain('Checking for updates');
	});
});
