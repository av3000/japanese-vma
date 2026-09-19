import React from 'react';
import { useParams } from 'react-router-dom';
import { useRadicalQuery } from '@/api/radicals/details';
import { Alert } from '@/components/shared/Alert';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container } from '@/components/shared/layout';
import styles from '../japaneseDetailPage.module.css';
import RadicalContent from './RadicalContent';

const RadicalDetails: React.FC = () => {
	const { radical_id } = useParams<{ radical_id: string }>();
	const { data: radical, isLoading, isError } = useRadicalQuery(radical_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !radical) {
		return (
			<Container className={styles.page}>
				<Alert tone="danger">Unable to load radical.</Alert>
			</Container>
		);
	}

	return <RadicalContent radical={radical} />;
};

export default RadicalDetails;
