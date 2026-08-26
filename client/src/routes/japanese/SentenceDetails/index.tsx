import React from 'react';
import { useParams } from 'react-router-dom';
import { useSentenceQuery } from '@/api/sentences/details';
import { PageLoading } from '@/components/shared/PageLoading';
import SentenceContent from './SentenceContent';

const SentenceDetails: React.FC = () => {
	const { sentence_id } = useParams<{ sentence_id: string }>();
	const { data: sentence, isLoading, isError } = useSentenceQuery(sentence_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !sentence) {
		return <div className="container mt-5 text-danger">Sentence could not be loaded.</div>;
	}

	return <SentenceContent sentence={sentence} />;
};

export default SentenceDetails;
