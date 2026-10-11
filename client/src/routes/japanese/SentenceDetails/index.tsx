import { useParams } from 'react-router-dom';
import { useSentenceQuery } from '@/api/sentences/details';
import { DetailUnavailable, unavailableMessage } from '@/components/shared/DetailLayout';
import { PageLoading } from '@/components/shared/PageLoading';
import SentenceContent from './SentenceContent';

const SentenceDetails = () => {
	const { sentence_id } = useParams<{ sentence_id: string }>();
	const { data: sentence, isLoading, isError, error } = useSentenceQuery(sentence_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !sentence) {
		return (
			<DetailUnavailable
				message={unavailableMessage(error, 'sentence')}
				backTo="/sentences"
				backLabel="Back to Sentences"
			/>
		);
	}

	return <SentenceContent sentence={sentence} />;
};

export default SentenceDetails;
