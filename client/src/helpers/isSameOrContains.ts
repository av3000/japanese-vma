export const isSameOrContains = (container: Element, element: Element): boolean => {
	if (container === element) return true;
	return container.contains(element);
};
