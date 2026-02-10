export function parseTags(input: string): string[] {
	if (!input.trim()) return [];
	return input
		.split(/[\s,]+/)
		.map((tag) => tag.trim())
		.filter(Boolean)
		.map((tag) => (tag.startsWith('#') ? tag.slice(1) : tag))
		.slice(0, 10);
}

export function formatTags(tags: Array<{ content: string }> | string[] | null | undefined): string {
	if (!tags || tags.length === 0) return '';

	const normalized = tags.map((tag) => {
		const value = typeof tag === 'string' ? tag : tag.content;
		const trimmed = value.trim();
		return trimmed.startsWith('#') ? trimmed : `#${trimmed}`;
	});

	return normalized.join(' ');
}
