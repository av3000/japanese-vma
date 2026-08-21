import React from 'react';
import { useParams } from 'react-router-dom';
import { useKanjiQuery } from '@/api/kanjis/details';
import { PageLoading } from '@/components/shared/PageLoading';
import KanjiContent from './KanjiContent';

const KanjiDetails: React.FC = () => {
	const { kanji_id } = useParams<{ kanji_id: string }>();
	const { data: kanji, isLoading, isError } = useKanjiQuery(kanji_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !kanji) {
		return (
			<div className="container mt-5 text-center">
				<p className="lead">Kanji not found.</p>
				<a href="/kanjis" className="btn btn-link">
					Back to all Kanjis
				</a>
			</div>
		);
	}

	return <KanjiContent kanji={kanji} />;
};

export default KanjiDetails;
