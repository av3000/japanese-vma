import { useQuery } from '@tanstack/react-query';
import type { RadicalResource } from '@/api/generated/model/radicalResource';
import { getRadicalShowQueryKey, radicalShow } from '@/api/generated/radical/radical';

export interface MappedRadical extends RadicalResource {
	kanjis: NonNullable<RadicalResource['kanjis']>;
}

export const mapRadicalDetail = (data: RadicalResource): MappedRadical => ({
	...data,
	kanjis: data.kanjis ?? [],
});

export const useRadicalQuery = (identifier: string | undefined) => {
	return useQuery({
		queryKey: identifier ? getRadicalShowQueryKey(identifier) : ['radical', 'missing-identifier'],
		queryFn: () => radicalShow(identifier as string),
		enabled: !!identifier,
		retry: false,
		select: mapRadicalDetail,
	});
};
