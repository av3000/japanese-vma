import { PublicityStatus } from '@/api/generated/model/publicityStatus';

/**
 * Named vocabulary for the generated `PublicityStatus` enum, which orval emits as
 * `NUMBER_0 .. NUMBER_1`. `satisfies` keeps the values pinned to the contract: if the backend
 * enum loses a member, this stops compiling.
 *
 * Mirrors `App\Domain\Shared\Enums\PublicityStatus`.
 */
export const PUBLICITY = {
	PRIVATE: PublicityStatus.NUMBER_0,
	PUBLIC: PublicityStatus.NUMBER_1,
} as const satisfies Record<string, PublicityStatus>;

export const isPublic = (publicity: PublicityStatus): boolean => publicity === PUBLICITY.PUBLIC;

/** Anything that is not explicitly public reads as private: the safer label for an owner. */
export const publicityLabel = (publicity: PublicityStatus): 'Public' | 'Private' =>
	isPublic(publicity) ? 'Public' : 'Private';
