export enum ObjectTemplateType {
	ARTICLE = 'ad69baf6-1a1f-42bd-8176-74ab5fbd69bd',
	ARTIST = '3105a1ce-c06f-4016-bf5b-b5287a023fd5',
	LYRIC = '2ce2d586-169a-4e41-9cdd-251e93fde5e2',
	RADICAL = 'e7367bcb-114e-4e89-b17f-810dfe87a3dc',
	KANJI = '6cd99a38-fa88-4558-9f68-0f2162576f36',
	WORD = 'd912962b-519e-4717-bcde-2cdd9fa00d37',
	SENTENCE = '91e47d5f-f994-4a9a-b1fc-53d63393bb70',
	LIST = '93edeaab-85d0-44ad-ba2d-4602ab4061ba',
	POST = 'a4b78a83-f180-49b5-9f8a-39500cd8fabf',
	COMMENT = '5ee9d6b7-aaae-4e0e-b63d-eae66ea49aef',
}

/**
 * Usage: `ObjectTemplateTypeLabel[ObjectTemplateType.ARTICLE];`
 */
export const ObjectTemplateTypeLabel: Record<ObjectTemplateType, string> = {
	[ObjectTemplateType.ARTICLE]: 'Article',
	[ObjectTemplateType.ARTIST]: 'Artist',
	[ObjectTemplateType.LYRIC]: 'Lyric',
	[ObjectTemplateType.RADICAL]: 'Radical',
	[ObjectTemplateType.KANJI]: 'Kanji',
	[ObjectTemplateType.WORD]: 'Word',
	[ObjectTemplateType.SENTENCE]: 'Sentence',
	[ObjectTemplateType.LIST]: 'List',
	[ObjectTemplateType.POST]: 'Post',
	[ObjectTemplateType.COMMENT]: 'Comment',
};

/**
 * Usage: `ObjectTemplateTypeLegacyId[ObjectTemplateType.COMMENT];`
 */
export const ObjectTemplateTypeLegacyId: Record<ObjectTemplateType, number> = {
	[ObjectTemplateType.ARTICLE]: 1,
	[ObjectTemplateType.ARTIST]: 2,
	[ObjectTemplateType.LYRIC]: 3,
	[ObjectTemplateType.RADICAL]: 4,
	[ObjectTemplateType.KANJI]: 5,
	[ObjectTemplateType.WORD]: 6,
	[ObjectTemplateType.SENTENCE]: 7,
	[ObjectTemplateType.LIST]: 8,
	[ObjectTemplateType.POST]: 9,
	[ObjectTemplateType.COMMENT]: 10,
};
