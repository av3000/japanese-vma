import { useParams } from 'react-router-dom';
import { useWordQuery } from '@/api/words/details';
import { DetailUnavailable, unavailableMessage } from '@/components/shared/DetailLayout';
import { PageLoading } from '@/components/shared/PageLoading';
import WordContent from './WordContent';

const WordDetails = () => {
	const { word_id } = useParams<{ word_id: string }>();
	const { data: word, isLoading, isError, error } = useWordQuery(word_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !word) {
		return (
			<DetailUnavailable message={unavailableMessage(error, 'word')} backTo="/words" backLabel="Back to Words" />
		);
	}

	return <WordContent word={word} />;
};

export default WordDetails;
