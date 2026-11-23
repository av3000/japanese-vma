import { useCallback, useRef } from 'react';
import type { InfiniteData } from '@tanstack/react-query';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import type { ConnectionStatus } from '@/lib/echo/types';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import { isNonTerminalProcessingStatus } from '../processingStatusCache';

/**
 * Polling fallback for article processing status (ADR 0002: polling is the correctness
 * baseline, the socket is the fast path).
 *
 * A query polls only while it holds at least one `pending` or `processing` status AND the
 * socket is not connected. That covers production without a Reverb service, anonymous users
 * who never connect, and any reconnect gap. When the socket is connected nothing polls.
 * After a minute of polling the interval backs off so a stuck job does not hammer the API.
 */

export const PROCESSING_POLL_FAST_MS = 5_000;
export const PROCESSING_POLL_SLOW_MS = 15_000;
export const PROCESSING_POLL_BACKOFF_AFTER_MS = 60_000;

export type ProcessingRefetchInterval = number | false;

export const resolveProcessingRefetchInterval = ({
	hasNonTerminal,
	connectionStatus,
	pollingSinceMs,
	nowMs,
}: {
	hasNonTerminal: boolean;
	connectionStatus: ConnectionStatus;
	pollingSinceMs: number | null;
	nowMs: number;
}): ProcessingRefetchInterval => {
	if (!hasNonTerminal || connectionStatus === 'connected') {
		return false;
	}

	const polledFor = pollingSinceMs === null ? 0 : nowMs - pollingSinceMs;

	return polledFor >= PROCESSING_POLL_BACKOFF_AFTER_MS ? PROCESSING_POLL_SLOW_MS : PROCESSING_POLL_FAST_MS;
};

export const detailHasNonTerminalProcessing = (data: ArticleDetailResource | undefined): boolean =>
	isNonTerminalProcessingStatus(data?.processing_status?.status);

export const listHasNonTerminalProcessing = (data: InfiniteData<ArticleListResource> | undefined): boolean =>
	data?.pages.some((page) =>
		page.items.some((item) => isNonTerminalProcessingStatus(item.processing_status?.status)),
	) ?? false;

/**
 * Returns a `refetchInterval` function for react-query. The function form is used on purpose:
 * react-query re-evaluates it after every fetch and whenever the observer's options change, so
 * the interval always reflects the data just fetched and the current socket state. Each query
 * owns its own interval; a detail page never polls the list and vice versa.
 *
 * @param hasNonTerminal reads the raw (pre-`select`) query data and says whether anything is
 *        still pending or processing.
 */
export const useProcessingRefetchInterval = <TData>(hasNonTerminal: (data: TData | undefined) => boolean) => {
	const { connectionStatus } = useWebSocket();
	const pollingSinceMs = useRef<number | null>(null);

	return useCallback(
		(query: { state: { data: TData | undefined } }): ProcessingRefetchInterval => {
			const nowMs = Date.now();
			const interval = resolveProcessingRefetchInterval({
				hasNonTerminal: hasNonTerminal(query.state.data),
				connectionStatus,
				pollingSinceMs: pollingSinceMs.current,
				nowMs,
			});

			pollingSinceMs.current = interval === false ? null : (pollingSinceMs.current ?? nowMs);

			return interval;
		},
		[connectionStatus, hasNonTerminal],
	);
};
