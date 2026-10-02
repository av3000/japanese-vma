import { useEffect, useState } from 'react';
import type { FieldPath, FieldValues, UseFormSetError } from 'react-hook-form';
import type { WriteFailure } from '@/api/writeFailure';
import { applyServerFieldErrors } from '@/helpers/applyServerFieldErrors';

/**
 * Applies a rejected save to a form: 422 messages go under their fields (focus moves to the first
 * one), and the return value is the one line for the form's general alert. When every message
 * found a field, that line is the failure's own "check the highlighted fields"; messages that
 * belong to no field are shown instead, since they are the only place the reader can see them.
 *
 * `fields` and `fieldMap` must be stable (module constants), or the effect re-runs every render.
 */
export function useWriteFailureAlert<TValues extends FieldValues>(
	failure: WriteFailure | null | undefined,
	setError: UseFormSetError<TValues>,
	fields: readonly FieldPath<TValues>[],
	fieldMap?: Partial<Record<string, FieldPath<TValues>>>,
): string | null {
	const [message, setMessage] = useState<string | null>(null);

	useEffect(() => {
		if (!failure) {
			setMessage(null);
			return;
		}

		if (failure.kind === 'validation') {
			const unmatched = applyServerFieldErrors(setError, failure.errors, fields, fieldMap);
			setMessage(unmatched.length > 0 ? unmatched.join(' ') : failure.message);
			return;
		}

		setMessage(failure.message);
	}, [failure, setError, fields, fieldMap]);

	return message;
}

export const NO_CHANGES_MESSAGE = 'No changes to save.';
