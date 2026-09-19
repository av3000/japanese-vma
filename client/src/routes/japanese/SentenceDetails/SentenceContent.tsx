import { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { canMutateSentence, readSentenceWriteError, useDeleteSentenceMutation } from '@/api/sentences/authoring';
import type { MappedSentenceDetail } from '@/api/sentences/details';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import CommentsBlock from '@/components/features/comment/CommentsBlock';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { Cluster, Container, Grid, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { SavedListType } from '@/shared/constants/enums';
import styles from '../japaneseDetailPage.module.css';

interface SentenceContentProps {
	sentence: MappedSentenceDetail;
}

const SentenceContent = ({ sentence }: SentenceContentProps) => {
	const { isAuthenticated, user } = useAuth();
	const navigate = useNavigate();

	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(deleteDialogRef, { id: 'sentence-delete-modal' });

	const [deleteError, setDeleteError] = useState<string | null>(null);

	const deleteMutation = useDeleteSentenceMutation(sentence);

	// One gate for both controls, so imported sentences stay read-only for admins too.
	const canMutate = canMutateSentence(user, sentence);

	const handleDelete = () => {
		setDeleteError(null);

		deleteMutation.mutate(undefined, {
			onSuccess: () => navigate('/sentences'),
			// Keep the modal open on failure — the sentence is still there.
			onError: (error) => setDeleteError(readSentenceWriteError(error).message),
		});
	};

	return (
		<Container className={styles.page}>
			<Stack gap="2xl">
				<div>
					<Link to="/sentences">Back</Link>
				</div>
				<Grid columns={12} gap="lg" align="start">
					<Grid.Item span={{ base: 12, sm: 8 }}>
						<h4 lang="ja">{sentence.content}</h4>
						{sentence.user_id ? (
							<p>User Author - {sentence.user_id}</p>
						) : (
							<p>
								Tatoeba link:{' '}
								<a
									href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`}
									target="_blank"
									rel="noopener noreferrer"
								>
									{sentence.tatoeba_entry}
								</a>
							</p>
						)}
					</Grid.Item>
					<Grid.Item span={{ base: 12, sm: 4 }}>
						<Cluster gap="xs" align="start">
							{canMutate && (
								<>
									<Button
										onClick={deleteModal.open}
										variant="ghost"
										hasOnlyIcon
										aria-label="Delete sentence"
										aria-controls={deleteModal.id}
										aria-expanded={deleteModal.isOpen}
									>
										<Icon name="trashbinSolid" size="md" />
									</Button>
									<Link to={`/sentences/${sentence.uuid}/edit`}>Edit</Link>
								</>
							)}
							{isAuthenticated && (
								<AuthorizedBookmarkWidget
									instanceObjectType={SavedListType.SENTENCES}
									isKnownType={SavedListType.KNOWNSENTENCES}
									entityId={sentence.id}
									modalTitle="Choose Sentence List to add"
								/>
							)}
						</Cluster>
					</Grid.Item>
				</Grid>
				{deleteError && <Alert tone="danger">{deleteError}</Alert>}

				<section className={styles.section}>
					<h4>Kanjis ({sentence.kanjis.length}) results</h4>
					<ul className={styles.relatedList}>
						{sentence.kanjis.map((kanji) => (
							<li className={styles.relatedRow} key={kanji.uuid}>
								<h3 lang="ja">{kanji.character}</h3>
								<span>{kanji.meanings.slice(0, 3).join(', ')}</span>
								<Link to={`/kanji/${kanji.uuid}`} className={styles.rowAction}>
									Open
								</Link>
							</li>
						))}
					</ul>
				</section>
			</Stack>

			{/* A sentence cannot be locked, so no `isLocked` is passed; the gate exists only for Post. */}
			<Container size="sm" as="section" className={styles.section}>
				<CommentsBlock parent="sentence" entityId={sentence.id} entityUuid={sentence.uuid} />
			</Container>

			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={sentence.content}
				onDelete={handleDelete}
				isProcessing={deleteMutation.isPending}
			/>
		</Container>
	);
};

export default SentenceContent;
