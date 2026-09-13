import React from 'react';
import { useParams } from 'react-router-dom';
import classNames from 'classnames';
import { useKanjiQuery } from '@/api/kanjis/details';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container, Stack } from '@/components/shared/layout';
import styles from '../japaneseDetailPage.module.css';
import KanjiContent from './KanjiContent';

const KanjiDetails: React.FC = () => {
	const { kanji_id } = useParams<{ kanji_id: string }>();
	const { data: kanji, isLoading, isError } = useKanjiQuery(kanji_id);

	if (isLoading) {
		return <PageLoading family="detail" />;
	}

	if (isError || !kanji) {
		return (
			<Container className={classNames(styles.page, styles.centered)}>
				<Stack gap="md" align="center">
					<p className={styles.lead}>Kanji not found.</p>
					<Button variant="linkButton" href="/kanjis">
						Back to all Kanjis
					</Button>
				</Stack>
			</Container>
		);
	}

	return <KanjiContent kanji={kanji} />;
};

export default KanjiDetails;
