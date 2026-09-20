/**
 * @vitest-environment jsdom
 */
import { useInfiniteQuery, useQuery, type InfiniteData } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import type { ConnectionStatus } from '@/lib/echo/types';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import { renderWithAct } from '@/test/renderWithAct';
import { useArticleQuery } from '../details';
import { useInfiniteArticles } from './useInfiniteArticles';
import {
	PROCESSING_POLL_BACKOFF_AFTER_MS,
	PROCESSING_POLL_FAST_MS,
	PROCESSING_POLL_SLOW_MS,
	detailHasNonTerminalProcessing,
	listHasNonTerminalProcessing,
	resolveProcessingRefetchInterval,
	useProcessingRefetchInterval,
	type ProcessingRefetchInterval,
} from './useProcessingPolling';

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return { ...actual, useQuery: vi.fn(), useInfiniteQuery: vi.fn() };
});

vi.mock('@/providers/contexts/socket-provider', () => ({
	useWebSocket: vi.fn(),
}));

const status = (value: ProcessingStatus): ProcessingStatusResource => ({
	id: 1,
	entity_id: 'entity-uuid',
	attempt: 1,
	max_attempts: 3,
	sequence: 1,
	type: 'kanji_extraction',
	status: value,
	metadata: {},
	created_at: '2026-09-19T10:00:00+00:00',
	updated_at: '2026-09-19T10:00:05+00:00',
});

const detail = (value: ProcessingStatus | null): ArticleDetailResource =>
	({ uid: 'u', processing_status: value ? status(value) : null }) as unknown as ArticleDetailResource;

const list = (...values: Array<ProcessingStatus | null>): InfiniteData<ArticleListResource> => ({
	pageParams: [1],
	pages: [
		{
			items: values.map((value, index) => ({
				uuid: `u${index}`,
				processing_status: value ? status(value) : null,
			})),
		} as unknown as ArticleListResource,
	],
});

const setSocket = (connectionStatus: ConnectionStatus) =>
	vi
		.mocked(useWebSocket)
		.mockReturnValue({ connectionStatus, isConnected: connectionStatus === 'connected' } as never);

type RefetchIntervalOption = (query: { state: { data: unknown } }) => ProcessingRefetchInterval;

describe('resolveProcessingRefetchInterval', () => {
	it('polls fast while something is non-terminal and the socket is not connected', () => {
		expect(
			resolveProcessingRefetchInterval({
				hasNonTerminal: true,
				connectionStatus: 'disconnected',
				pollingSinceMs: null,
				nowMs: 0,
			}),
		).toBe(PROCESSING_POLL_FAST_MS);
	});

	it('backs off once polling has run for the backoff window', () => {
		expect(
			resolveProcessingRefetchInterval({
				hasNonTerminal: true,
				connectionStatus: 'failed',
				pollingSinceMs: 0,
				nowMs: PROCESSING_POLL_BACKOFF_AFTER_MS,
			}),
		).toBe(PROCESSING_POLL_SLOW_MS);
	});

	it('does not poll when everything is terminal or the socket is connected', () => {
		expect(
			resolveProcessingRefetchInterval({
				hasNonTerminal: false,
				connectionStatus: 'disconnected',
				pollingSinceMs: null,
				nowMs: 0,
			}),
		).toBe(false);
		expect(
			resolveProcessingRefetchInterval({
				hasNonTerminal: true,
				connectionStatus: 'connected',
				pollingSinceMs: null,
				nowMs: 0,
			}),
		).toBe(false);
	});
});

describe('non-terminal detectors', () => {
	it('read the detail and any list page', () => {
		expect(detailHasNonTerminalProcessing(detail(ProcessingStatus.pending))).toBe(true);
		expect(detailHasNonTerminalProcessing(detail(ProcessingStatus.completed))).toBe(false);
		expect(detailHasNonTerminalProcessing(detail(null))).toBe(false);
		expect(detailHasNonTerminalProcessing(undefined)).toBe(false);

		expect(listHasNonTerminalProcessing(list(null, ProcessingStatus.completed, ProcessingStatus.processing))).toBe(
			true,
		);
		expect(listHasNonTerminalProcessing(list(null, ProcessingStatus.completed))).toBe(false);
		expect(listHasNonTerminalProcessing(undefined)).toBe(false);
	});
});

describe('useProcessingRefetchInterval', () => {
	afterEach(() => {
		vi.useRealTimers();
	});

	it('tracks how long it has been polling and resets when polling stops', async () => {
		vi.useFakeTimers();
		vi.setSystemTime(0);
		setSocket('disconnected');

		let interval!: RefetchIntervalOption;
		const Probe = () => {
			interval = useProcessingRefetchInterval<boolean>(
				(data) => data === true,
			) as unknown as RefetchIntervalOption;
			return null;
		};
		const { unmount } = await renderWithAct(<Probe />);

		expect(interval({ state: { data: true } })).toBe(PROCESSING_POLL_FAST_MS);
		vi.setSystemTime(PROCESSING_POLL_BACKOFF_AFTER_MS - 1);
		expect(interval({ state: { data: true } })).toBe(PROCESSING_POLL_FAST_MS);
		vi.setSystemTime(PROCESSING_POLL_BACKOFF_AFTER_MS);
		expect(interval({ state: { data: true } })).toBe(PROCESSING_POLL_SLOW_MS);

		// Terminal data stops polling and clears the window, so a later run starts fast again.
		expect(interval({ state: { data: false } })).toBe(false);
		vi.setSystemTime(PROCESSING_POLL_BACKOFF_AFTER_MS * 2);
		expect(interval({ state: { data: true } })).toBe(PROCESSING_POLL_FAST_MS);

		await unmount();
	});
});

describe('article queries wire the polling interval', () => {
	beforeEach(() => {
		vi.mocked(useQuery).mockReset();
		vi.mocked(useInfiniteQuery).mockReset();
	});

	const captureDetailInterval = async (connectionStatus: ConnectionStatus) => {
		setSocket(connectionStatus);
		let refetchInterval!: RefetchIntervalOption;
		vi.mocked(useQuery).mockImplementation(((options: { refetchInterval: RefetchIntervalOption }) => {
			refetchInterval = options.refetchInterval;
			return { data: undefined } as never;
		}) as never);

		const Probe = () => {
			useArticleQuery('u');
			return null;
		};
		const { unmount } = await renderWithAct(<Probe />);
		await unmount();

		return refetchInterval;
	};

	it('detail: pending + disconnected polls, completed does not, connected does not', async () => {
		const disconnected = await captureDetailInterval('disconnected');
		expect(disconnected({ state: { data: detail(ProcessingStatus.pending) } })).toBeGreaterThan(0);
		expect(disconnected({ state: { data: detail(ProcessingStatus.completed) } })).toBe(false);

		const connected = await captureDetailInterval('connected');
		expect(connected({ state: { data: detail(ProcessingStatus.pending) } })).toBe(false);
	});

	it('list: polls when any loaded item is non-terminal and the socket is disconnected', async () => {
		setSocket('disconnected');
		let refetchInterval!: RefetchIntervalOption;
		vi.mocked(useInfiniteQuery).mockImplementation(((options: { refetchInterval: RefetchIntervalOption }) => {
			refetchInterval = options.refetchInterval;
			return { data: undefined } as never;
		}) as never);

		const Probe = () => {
			useInfiniteArticles({ filters: { per_page: 4 } });
			return null;
		};
		const { unmount } = await renderWithAct(<Probe />);
		await unmount();

		expect(refetchInterval({ state: { data: list(null, ProcessingStatus.processing) } })).toBe(
			PROCESSING_POLL_FAST_MS,
		);
		expect(refetchInterval({ state: { data: list(null, ProcessingStatus.completed) } })).toBe(false);
	});
});
