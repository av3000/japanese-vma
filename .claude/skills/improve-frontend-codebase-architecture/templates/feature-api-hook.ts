import { useQuery } from '@tanstack/react-query';
import {
	/* generatedClient */,
	/* getGeneratedQueryKey */,
} from '@/api/generated/<feature>/<feature>';

type UseFeatureThingOptions = {
	enabled?: boolean;
	/* params: GeneratedParams */
};

export const getFeatureThingQueryKey = (/* params */) => {
	return /* getGeneratedQueryKey(params) */;
};

export const useFeatureThing = ({ enabled = true }: UseFeatureThingOptions = {}) => {
	return useQuery({
		queryKey: getFeatureThingQueryKey(/* params */),
		queryFn: ({ signal }) => {
			return /* generatedClient(params, undefined, signal) */;
		},
		enabled,
		select: (data) => {
			// Map generated wire shape to route-friendly data only when it adds leverage.
			return data;
		},
	});
};
