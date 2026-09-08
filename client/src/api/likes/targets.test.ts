import { describe, expect, it } from 'vitest';
import { LikeTargetType } from '@/api/generated/model/likeTargetType';
import { ObjectTemplateType, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import {
	assertLikeInstanceId,
	isLikeTargetTemplate,
	LikeContractError,
	LIKE_TARGET_TEMPLATES,
	toLikeTargetType,
} from './targets';

describe('like target mapping', () => {
	it('covers exactly the likeable subset the contract declares', () => {
		expect([...LIKE_TARGET_TEMPLATES].map(toLikeTargetType).sort((a, b) => a - b)).toEqual(
			Object.values(LikeTargetType).sort((a, b) => a - b),
		);
	});

	it.each([
		[ObjectTemplateType.ARTICLE, LikeTargetType.NUMBER_1],
		[ObjectTemplateType.LIST, LikeTargetType.NUMBER_8],
		[ObjectTemplateType.POST, LikeTargetType.NUMBER_9],
		[ObjectTemplateType.COMMENT, LikeTargetType.NUMBER_10],
	])('maps %s to its legacy template id', (template, expected) => {
		expect(toLikeTargetType(template)).toBe(expected);
		// The wire value must stay the same number the rest of the app knows this template by.
		expect(toLikeTargetType(template)).toBe(ObjectTemplateTypeLegacyId[template]);
	});

	it.each([ObjectTemplateType.KANJI, ObjectTemplateType.WORD, ObjectTemplateType.SENTENCE])(
		'refuses %s, which has no like storage behind it',
		(template) => {
			expect(isLikeTargetTemplate(template)).toBe(false);
			expect(() => toLikeTargetType(template)).toThrow(LikeContractError);
		},
	);
});

describe('like instance id guard', () => {
	it('accepts a loaded positive integer id', () => {
		expect(assertLikeInstanceId(42)).toBe(42);
	});

	it('refuses a uuid route parameter instead of coercing it to a numeric id', () => {
		expect(() => assertLikeInstanceId(ObjectTemplateType.ARTICLE)).toThrow(LikeContractError);
		expect(() => assertLikeInstanceId('9d5e5b3a-1c2d-4f6a-8b7c-0e1f2a3b4c5d')).toThrow(LikeContractError);
	});

	it.each([[Number.NaN], [0], [-1], [1.5], [undefined], [null]])('refuses %s', (value) => {
		expect(() => assertLikeInstanceId(value)).toThrow(LikeContractError);
	});
});
