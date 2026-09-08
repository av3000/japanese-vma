import { useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import type { SentenceResource } from '@/api/generated/model/sentenceResource';
import type { StoreSentenceRequest } from '@/api/generated/model/storeSentenceRequest';
import type { UpdateSentenceRequest } from '@/api/generated/model/updateSentenceRequest';
import {
	getSentenceIndexQueryKey,
	getSentenceShowQueryKey,
	sentenceDestroy,
	sentenceStore,
	sentenceUpdate,
} from '@/api/generated/sentence/sentence';
import { readWriteFailure, type WriteFailure } from '@/api/writeFailure';
import type { User } from '@/types';

export type SentenceWriteResponse = SentenceResource;

type SentenceIdentity = Pick<SentenceWriteResponse, 'id' | 'uuid'>;
type SentenceOwnership = Pick<SentenceWriteResponse, 'user_id'>;
type SentenceViewer = Pick<User, 'id' | 'isAdmin'> | null | undefined;

export const GENERIC_SENTENCE_WRITE_ERROR = 'Something went wrong. Please try again.';

/**
 * Mirrors `SentencePolicy::isImmutable` — imported sentences have no author and
 * stay immutable, including for admins.
 */
export const isImportedSentence = (sentence: SentenceOwnership): boolean => sentence.user_id === null;

/**
 * Mirrors `SentencePolicy::canMutate`. The imported check runs before the admin
 * check on purpose: an admin-first gate would render controls the server then
 * rejects with 403. The server stays authoritative either way.
 */
export const canMutateSentence = (viewer: SentenceViewer, sentence: SentenceOwnership): boolean => {
	if (!viewer || isImportedSentence(sentence)) {
		return false;
	}

	return viewer.isAdmin || viewer.id === sentence.user_id;
};

/**
 * The detail route resolves both UUIDs and legacy numeric ids, and the generated
 * key is built from whichever identifier the URL carried. A viewer who arrived at
 * `/sentence/77` has their detail cached under `['/sentences/77']`, so a write has
 * to reconcile both entries or the page keeps rendering pre-write content.
 */
export const sentenceDetailQueryKeys = (sentence: SentenceIdentity) =>
	[getSentenceShowQueryKey(sentence.uuid), getSentenceShowQueryKey(String(sentence.id))] as const;

export const reconcileSentenceCaches = (queryClient: QueryClient, sentence: SentenceWriteResponse): void => {
	for (const queryKey of sentenceDetailQueryKeys(sentence)) {
		queryClient.setQueryData(queryKey, sentence);
	}

	queryClient.invalidateQueries({ queryKey: getSentenceIndexQueryKey() });
};

export const evictSentenceCaches = (queryClient: QueryClient, sentence: SentenceIdentity): void => {
	for (const queryKey of sentenceDetailQueryKeys(sentence)) {
		queryClient.removeQueries({ queryKey });
	}

	queryClient.invalidateQueries({ queryKey: getSentenceIndexQueryKey() });
};

/**
 * `POST /v1/sentences` answers 201 with a sentence, but Scramble also documents an
 * inferred `200: string` for it, so Orval widens the client to `string | SentenceResource`.
 * The string arm never occurs at runtime. Narrowing here keeps the wire-shape noise at
 * the adapter boundary instead of leaking a cast into the create route.
 *
 * TODO: drop this once the spurious 200 is gone from api.json — it also affects
 * `POST /comments`, `POST /register` and `POST /catalogues/{uuid}/items`.
 */
const asSentenceResource = (response: string | SentenceWriteResponse): SentenceWriteResponse => {
	if (typeof response === 'string') {
		throw new Error('Sentence create returned an unexpected response shape.');
	}

	return response;
};

export type SentenceWriteFailure = WriteFailure;

export const readSentenceWriteError = (error: unknown): SentenceWriteFailure =>
	readWriteFailure(error, GENERIC_SENTENCE_WRITE_ERROR);

export const useCreateSentenceMutation = () => {
	const queryClient = useQueryClient();

	return useMutation<SentenceWriteResponse, unknown, StoreSentenceRequest>({
		mutationFn: async (payload) => asSentenceResource(await sentenceStore(payload)),
		onSuccess: (sentence) => reconcileSentenceCaches(queryClient, sentence),
	});
};

export const useUpdateSentenceMutation = (uuid: string) => {
	const queryClient = useQueryClient();

	return useMutation<SentenceWriteResponse, unknown, UpdateSentenceRequest>({
		mutationFn: (payload) => sentenceUpdate(uuid, payload),
		onSuccess: (sentence) => reconcileSentenceCaches(queryClient, sentence),
	});
};

export const useDeleteSentenceMutation = (sentence: SentenceIdentity) => {
	const queryClient = useQueryClient();

	// Type arguments are left to inference: an explicit `void` type argument trips
	// @typescript-eslint/no-invalid-void-type.
	return useMutation({
		// The delete body is intentionally discarded; the endpoint answers 204.
		mutationFn: async (): Promise<void> => {
			await sentenceDestroy(sentence.uuid);
		},
		onSuccess: () => evictSentenceCaches(queryClient, sentence),
	});
};
