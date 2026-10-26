import { useQuery } from '@tanstack/react-query';
import { getRadicalShowQueryKey, radicalShow } from '@/api/generated/radical/radical';

/** The radical's own fields. Its kanji come paged from `/kanjis?radical_uuid=` (`api/radicals/related.ts`). */
export const useRadicalQuery = (identifier: string | undefined) => {
	return useQuery({
		queryKey: identifier ? getRadicalShowQueryKey(identifier) : ['radical', 'missing-identifier'],
		queryFn: () => radicalShow(identifier as string),
		enabled: !!identifier,
		retry: false,
	});
};
