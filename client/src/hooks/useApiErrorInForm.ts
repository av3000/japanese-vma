import { useEffect, useState } from 'react';
import type { FieldPath, FieldValues, UseFormSetError } from 'react-hook-form';
import type { ApiError } from '@/api/apiError';
import { setApiFieldErrors } from '@/helpers/formErrors';

/**
 * Shows a failed save in a form: the API's field messages go under their fields (focus moves to
 * the first one), and the return value is the one line for the form's top alert. When every message
 * found a field, that line is "Check the highlighted fields…"; messages that belong to no field are
 * shown instead, since the alert is the only place the reader can see them.
 *
 * `fields` and `fieldMap` must be stable (module constants), or the effect re-runs every render.
 */
export function useApiErrorInForm<TValues extends FieldValues>(
	apiError: ApiError | null | undefined,
	setError: UseFormSetError<TValues>,
	fields: readonly FieldPath<TValues>[],
	fieldMap?: Partial<Record<string, FieldPath<TValues>>>,
): string | null {
	const [alertMessage, setAlertMessage] = useState<string | null>(null);

	useEffect(() => {
		if (!apiError) {
			setAlertMessage(null);
			return;
		}

		if (apiError.kind === 'validation') {
			const unmatched = setApiFieldErrors(setError, apiError.errors, fields, fieldMap);
			setAlertMessage(unmatched.length > 0 ? unmatched.join(' ') : apiError.message);
			return;
		}

		setAlertMessage(apiError.message);
	}, [apiError, setError, fields, fieldMap]);

	return alertMessage;
}

export const NO_CHANGES_MESSAGE = 'No changes to save.';
