export const isArrayUnique = (values: string[]) => {
	const seen = new Set<string>();
	for (const value of values) {
		const normalized = value.toLowerCase();
		if (seen.has(normalized)) return false;
		seen.add(normalized);
	}
	return true;
};
