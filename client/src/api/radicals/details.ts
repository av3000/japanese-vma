import { useQuery } from '@tanstack/react-query';
import { getRadicalShowQueryKey, radicalShow } from '@/api/generated/radical/radical';
import type { RadicalShow200 } from '@/api/generated/model/radicalShow200';

export interface MappedRadical extends RadicalShow200 {
	kanjis: NonNullable<RadicalShow200['kanjis']>;
}

export const mapRadicalDetail = (data: RadicalShow200): MappedRadical => ({
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
