import { LikeTargetType } from '@/api/generated/model/likeTargetType';
import { ObjectTemplateType } from '@/shared/constants/enums';

/**
 * Raised when a caller reaches the Like seam with something the contract cannot accept.
 *
 * Both failure modes it covers are programming errors rather than user input, so they should
 * surface loudly in development instead of travelling to the API and coming back as a 422.
 */
export class LikeContractError extends Error {
	constructor(message: string) {
		super(message);
		this.name = 'LikeContractError';
	}
}

/**
 * The object templates the v1 `like-instance` contract accepts, mirroring the `LikeTargetType`
 * subset of `ObjectTemplateType`. Anything outside this list has no Like storage behind it.
 */
export const LIKE_TARGET_TEMPLATES = [
	ObjectTemplateType.ARTICLE,
	ObjectTemplateType.LIST,
	ObjectTemplateType.POST,
	ObjectTemplateType.COMMENT,
] as const;

export type LikeTargetTemplate = (typeof LIKE_TARGET_TEMPLATES)[number];

/**
 * The one place a UI-facing object template turns into the numeric `template_id` the wire wants.
 *
 * Callers pass the UUID enum they already use everywhere else, so no route or component has to
 * know that the endpoint still speaks in legacy numeric template IDs.
 */
const LIKE_TARGET_TYPE_BY_TEMPLATE = {
	[ObjectTemplateType.ARTICLE]: LikeTargetType.NUMBER_1,
	[ObjectTemplateType.LIST]: LikeTargetType.NUMBER_8,
	[ObjectTemplateType.POST]: LikeTargetType.NUMBER_9,
	[ObjectTemplateType.COMMENT]: LikeTargetType.NUMBER_10,
} satisfies Record<LikeTargetTemplate, LikeTargetType>;

export const isLikeTargetTemplate = (template: ObjectTemplateType): template is LikeTargetTemplate =>
	Object.prototype.hasOwnProperty.call(LIKE_TARGET_TYPE_BY_TEMPLATE, template);

export const toLikeTargetType = (template: ObjectTemplateType): LikeTargetType => {
	if (!isLikeTargetTemplate(template)) {
		throw new LikeContractError(`Object template "${template}" cannot be liked.`);
	}

	return LIKE_TARGET_TYPE_BY_TEMPLATE[template];
};

/**
 * `real_object_id` addresses a loaded row, never a route parameter.
 *
 * Detail routes are keyed by UUID, so the tempting shortcut is to reuse the URL segment here.
 * `Number('a4b78a83-...')` is `NaN`, which would serialize as `null` and silently like nothing,
 * so the guard rejects anything that is not already a positive integer read off a loaded record.
 */
export const assertLikeInstanceId = (instanceId: unknown): number => {
	if (typeof instanceId !== 'number' || !Number.isInteger(instanceId) || instanceId < 1) {
		throw new LikeContractError(
			`Like target id must be a loaded positive integer, received ${JSON.stringify(instanceId)}.`,
		);
	}

	return instanceId;
};
