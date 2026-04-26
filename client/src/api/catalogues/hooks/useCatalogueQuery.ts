import { useQuery } from '@tanstack/react-query';
import { fetchCatalogue } from '../catalogues';

export const useCatalogueQuery = (uuid: string | undefined, enabled = true) => {
	return useQuery({
		queryKey: ['catalogue', uuid],
		queryFn: () => fetchCatalogue(uuid as string),
		enabled: enabled && !!uuid,
		retry: false,
	});
};
