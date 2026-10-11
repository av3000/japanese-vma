import { useParams } from 'react-router-dom';
import { useRadicalQuery } from '@/api/radicals/details';
import { DetailUnavailable, unavailableMessage } from '@/components/shared/DetailLayout';
import { PageLoading } from '@/components/shared/PageLoading';
import RadicalContent from './RadicalContent';

const RadicalDetails = () => {
	const { radical_id } = useParams<{ radical_id: string }>();
	const { data: radical, isLoading, isError, error } = useRadicalQuery(radical_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !radical) {
		return (
			<DetailUnavailable
				message={unavailableMessage(error, 'radical')}
				backTo="/radicals"
				backLabel="Back to Radicals"
			/>
		);
	}

	return <RadicalContent radical={radical} />;
};

export default RadicalDetails;
