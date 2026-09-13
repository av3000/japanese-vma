import React from 'react';
import { useParams } from 'react-router-dom';
import { useWordQuery } from '@/api/words/details';
import { Alert } from '@/components/shared/Alert';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container } from '@/components/shared/layout';
import styles from '../japaneseDetailPage.module.css';
import WordContent from './WordContent';

const WordDetails: React.FC = () => {
	const { word_id } = useParams<{ word_id: string }>();
	const { data: word, isLoading, isError } = useWordQuery(word_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !word) {
		return (
			<Container className={styles.page}>
				<Alert tone="danger">Unable to load word.</Alert>
			</Container>
		);
	}

	return <WordContent word={word} />;
};

export default WordDetails;
