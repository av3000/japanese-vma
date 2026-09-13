import React from 'react';
import { useParams } from 'react-router-dom';
import classNames from 'classnames';
import { useWordQuery } from '@/api/words/details';
import Spinner from '@/assets/images/spinner.gif';
import { Alert } from '@/components/shared/Alert';
import { Container } from '@/components/shared/layout';
import styles from '../japaneseDetailPage.module.css';
import WordContent from './WordContent';

const WordDetails: React.FC = () => {
	const { word_id } = useParams<{ word_id: string }>();
	const { data: word, isLoading, isError } = useWordQuery(word_id);

	if (isLoading) {
		return (
			<Container className={classNames(styles.page, styles.centered)}>
				<img src={Spinner} alt="Loading..." />
			</Container>
		);
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
