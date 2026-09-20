export const stringifyCatalogueTags = (tags: string[]) => {
	const uniqueTags = Array.from(
		new Set(
			tags
				.map((tag) => tag.trim())
				.filter(Boolean)
				.map((tag) => (tag.startsWith('#') ? tag : `#${tag}`)),
		),
	);

	return uniqueTags.join(' ');
};
