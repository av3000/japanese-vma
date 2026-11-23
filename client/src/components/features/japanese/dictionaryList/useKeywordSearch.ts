import * as React from 'react';
import { useSearchParams } from 'react-router-dom';

/**
 * The URL side of a dictionary list: the applied keyword comes from `?keyword=`, and a search
 * replaces the whole query string with the non-empty values it is given, in the order it is given.
 * `applySearch` is stable, so routes can pass it to memoised children.
 */
export const useKeywordSearch = () => {
	const [searchParams, setSearchParams] = useSearchParams();
	const keyword = searchParams.get('keyword')?.trim() ?? '';

	const applySearch = React.useCallback(
		(values: Record<string, string>) => {
			const nextParams = new URLSearchParams();

			for (const [name, value] of Object.entries(values)) {
				if (value !== '') {
					nextParams.set(name, value);
				}
			}

			setSearchParams(nextParams);
		},
		[setSearchParams],
	);

	return { searchParams, keyword, applySearch };
};
