import React from 'react';
import { useParams } from 'react-router-dom';
import { useSentenceQuery } from '@/api/sentences/details';
import { Alert } from '@/components/shared/Alert';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container } from '@/components/shared/layout';
import styles from '../japaneseDetailPage.module.css';
import SentenceContent from './SentenceContent';

const SentenceDetails: React.FC = () => {
	const { sentence_id } = useParams<{ sentence_id: string }>();
	const { data: sentence, isLoading, isError } = useSentenceQuery(sentence_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !sentence) {
		return (
			<Container className={styles.page}>
				<Alert tone="danger">Sentence could not be loaded.</Alert>
			</Container>
		);
	}

	return <SentenceContent sentence={sentence} />;
};

export default SentenceDetails;
