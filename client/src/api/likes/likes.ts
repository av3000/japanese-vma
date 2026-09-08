import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { QueryClient, QueryKey, UseMutationOptions } from '@tanstack/react-query';
import { likeLikeInstance } from '@/api/generated/like/like';
import type { LikeLikeInstanceMutationError } from '@/api/generated/like/like';
import type { LikeToggleResource } from '@/api/generated/model/likeToggleResource';
import { assertLikeInstanceId, toLikeTargetType, type LikeTargetTemplate } from './targets';

/**
 * The like facts a cached record can hold. Identical in shape to the toggle response, so the
 * authoritative server state can be written back through the same binding as an optimistic guess.
 */
export type LikeState = LikeToggleResource;

export type LikeToggleError = LikeLikeInstanceMutationError;

/**
 * How one domain's cache stores the like state for a target.
 *
 * The shared seam owns the request, the mutation key, the optimistic flip and the rollback; the
 * domain owns the shape of its own cache entry. `read` returning `undefined` means the domain has
 * nothing to flip optimistically - the cache is then only moved by the authoritative response.
 */
export interface LikeCacheBinding<TCached> {
	queryKey: QueryKey;
	read: (cached: TCached, instanceId: number) => LikeState | undefined;
	write: (cached: TCached, instanceId: number, next: LikeState) => TCached;
}

/**
 * Mutation key for every Like toggle of one target kind, so pending Like traffic is addressable
 * without each domain inventing its own string.
 */
export const getLikeInstanceMutationKey = (template: LikeTargetTemplate): QueryKey => ['like-instance', template];

export interface LikeToggleContext<TCached> {
	previous: TCached | undefined;
}

/**
 * The toggle is a flip, so the optimistic guess is the inverse of what the cache already holds.
 * The count is floored because a stale zero must not render as `-1` while the request is in flight.
 */
const flipLikeState = ({ is_liked, likes_count }: LikeState): LikeState => ({
	is_liked: !is_liked,
	likes_count: Math.max(0, likes_count + (is_liked ? -1 : 1)),
});

interface LikeToggleMutationOptionsArgs<TCached> {
	queryClient: QueryClient;
	template: LikeTargetTemplate;
	binding: LikeCacheBinding<TCached>;
}

/**
 * Built separately from the hook so the cache behaviour can be exercised against a real
 * `QueryClient` without rendering a component.
 */
export const buildLikeToggleMutationOptions = <TCached>({
	queryClient,
	template,
	binding,
}: LikeToggleMutationOptionsArgs<TCached>): UseMutationOptions<
	LikeToggleResource,
	LikeToggleError,
	number,
	LikeToggleContext<TCached>
> => ({
	mutationKey: getLikeInstanceMutationKey(template),

	// `async` so a rejected contract guard surfaces as a mutation error rather than a synchronous
	// throw out of the click handler.
	mutationFn: async (instanceId) =>
		likeLikeInstance({
			template_id: toLikeTargetType(template),
			real_object_id: assertLikeInstanceId(instanceId),
		}),

	onMutate: async (instanceId) => {
		// An in-flight read would otherwise land after the optimistic write and undo it.
		await queryClient.cancelQueries({ queryKey: binding.queryKey });

		const previous = queryClient.getQueryData<TCached>(binding.queryKey);
		const current = previous === undefined ? undefined : binding.read(previous, instanceId);

		if (previous !== undefined && current !== undefined) {
			queryClient.setQueryData<TCached>(
				binding.queryKey,
				binding.write(previous, instanceId, flipLikeState(current)),
			);
		}

		return { previous };
	},

	onError: (_error, _instanceId, context) => {
		// Restore the exact snapshot rather than flipping back, so a concurrent server value that
		// arrived between the click and the failure is not reconstructed from a stale guess.
		if (context?.previous !== undefined) {
			queryClient.setQueryData<TCached>(binding.queryKey, context.previous);
		}
	},

	onSuccess: (result, instanceId) => {
		queryClient.setQueryData<TCached>(binding.queryKey, (cached) =>
			cached === undefined ? cached : binding.write(cached, instanceId, result),
		);
	},
});

/**
 * The single Like mutation seam. Every Like caller in the app goes through here.
 *
 * The target id is the mutation variable rather than a hook argument so one hook can serve a list
 * of targets - a comment thread - while still reporting which one is mid-flight.
 */
export const useToggleLikeMutation = <TCached>({
	template,
	binding,
}: Omit<LikeToggleMutationOptionsArgs<TCached>, 'queryClient'>) => {
	const queryClient = useQueryClient();
	const mutation = useMutation(buildLikeToggleMutationOptions<TCached>({ queryClient, template, binding }));

	return {
		...mutation,
		/**
		 * Duplicate-click guard. A second click on the same target while the first is in flight would
		 * flip the optimistic state back and leave the UI showing the opposite of the final response.
		 */
		isTogglingInstance: (instanceId: number) => mutation.isPending && mutation.variables === instanceId,
	};
};
