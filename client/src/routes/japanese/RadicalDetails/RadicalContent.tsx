import type { MappedRadical } from '@/api/radicals/details';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Link } from '@/components/shared/Link';
import { Container, Grid, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';
import styles from '../japaneseDetailPage.module.css';

interface RadicalContentProps {
	radical: MappedRadical;
}

const RadicalContent = ({ radical }: RadicalContentProps) => {
	const { isAuthenticated } = useAuth();

	return (
		<Container className={styles.page}>
			<Stack gap="2xl">
				<div>
					<Link to="/radicals">Back</Link>
				</div>
				<Grid columns={12} gap="lg">
					<Grid.Item span={{ base: 12, sm: 6 }}>
						<h1>
							{radical.radical} <br /> {radical.hiragana}
						</h1>
					</Grid.Item>
					<Grid.Item span={{ base: 12, sm: 6 }}>
						<p>meaning: {radical.meaning}</p>
						<p>strokes: {radical.strokes}</p>
						{isAuthenticated && (
							<AuthorizedBookmarkWidget
								instanceObjectType={SavedListType.RADICALS}
								isKnownType={SavedListType.KNOWNRADICALS}
								entityId={radical.id}
								modalTitle="Choose Radical List to add"
							/>
						)}
					</Grid.Item>
				</Grid>
				{radical.kanjis.length > 0 && (
					<section className={styles.section}>
						<h4>Kanjis ({radical.kanjis.length}) results</h4>
						<ul className={styles.relatedList}>
							{radical.kanjis.map((kanji) => (
								<li className={styles.relatedRow} key={kanji.uuid}>
									<h3>{kanji.character}</h3>
									<span>{kanji.meanings.join(', ')}</span>
									<Link to={`/kanji/${kanji.uuid}`} className={styles.rowAction}>
										Open
									</Link>
								</li>
							))}
						</ul>
					</section>
				)}
			</Stack>
		</Container>
	);
};

export default RadicalContent;
