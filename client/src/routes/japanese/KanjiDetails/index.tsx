import { useParams } from 'react-router-dom';
import { useKanjiQuery } from '@/api/kanjis/details';
import { DetailUnavailable, unavailableMessage } from '@/components/shared/DetailLayout';
import { PageLoading } from '@/components/shared/PageLoading';
import KanjiContent from './KanjiContent';

const KanjiDetails = () => {
	const { kanji_id } = useParams<{ kanji_id: string }>();
	const { data: kanji, isLoading, isError, error } = useKanjiQuery(kanji_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !kanji) {
		return (
			<DetailUnavailable
				message={unavailableMessage(error, 'kanji')}
				backTo="/kanjis"
				backLabel="Back to Kanji"
			/>
		);
	}

	return <KanjiContent kanji={kanji} />;
};

export default KanjiDetails;
