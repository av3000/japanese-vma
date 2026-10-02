import type { FieldError, FieldPath, FieldValues, UseFormSetError } from 'react-hook-form';

/**
 * Puts a 422's messages on the form fields they belong to and moves focus to the first of them, in
 * the order the form lists its fields rather than the order the server happened to send. Server
 * names are mapped through `fieldMap` (`hashtags` → `tags`) after dropping array indexes, so
 * `tags.3` reports against the tags control as a whole.
 *
 * Every message for a field is kept under `types.server`, so a field can list several broken rules.
 * Returns the messages that matched no field, for the form's general alert.
 */
export function applyServerFieldErrors<TValues extends FieldValues>(
	setError: UseFormSetError<TValues>,
	errors: Record<string, string[] | undefined>,
	fields: readonly FieldPath<TValues>[],
	fieldMap: Partial<Record<string, FieldPath<TValues>>> = {},
): string[] {
	const byField = new Map<FieldPath<TValues>, string[]>();
	const unmatched: string[] = [];

	for (const [rawField, messages] of Object.entries(errors)) {
		const present = (messages ?? []).filter(Boolean);
		if (present.length === 0) continue;

		const baseField = rawField.split('.')[0];
		const candidate = fieldMap[baseField] ?? baseField;
		const field = fields.find((name) => name === candidate);

		if (field === undefined) {
			unmatched.push(...present);
			continue;
		}

		byField.set(field, [...(byField.get(field) ?? []), ...present]);
	}

	let focused = false;
	for (const field of fields) {
		const messages = byField.get(field);
		if (!messages) continue;

		const unique = [...new Set(messages)];
		setError(field, { type: 'server', message: unique[0], types: { server: unique } }, { shouldFocus: !focused });
		focused = true;
	}

	return unmatched;
}

/**
 * Every message on a field, for `FormField`'s `error`. Server errors set by `applyServerFieldErrors`
 * carry all their messages under `types`; a client error carries one `message`. An array field
 * (tags) can also carry one error per item, or one for the whole list under `root`.
 */
export const fieldErrorMessages = (error: unknown): string[] => {
	if (!error || typeof error !== 'object') return [];
	if (Array.isArray(error)) return [...new Set(error.flatMap(fieldErrorMessages))];

	const { message, types, root } = error as Partial<FieldError> & { root?: FieldError };
	if (types) {
		return Object.values(types).flatMap((value) =>
			typeof value === 'string' ? [value] : Array.isArray(value) ? value : [],
		);
	}
	if (typeof message === 'string' && message) return [message];

	return fieldErrorMessages(root);
};
