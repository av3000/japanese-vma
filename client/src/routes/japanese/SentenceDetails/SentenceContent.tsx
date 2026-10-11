import { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { parseApiError } from '@/api/apiError';
import { canMutateSentence, useDeleteSentenceMutation } from '@/api/sentences/authoring';
import type { MappedSentenceDetail } from '@/api/sentences/details';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { EntryHeader, SentenceSourceLine } from '@/components/features/japanese/entry';
import { sentenceSource, type SentenceSource } from '@/components/features/japanese/entryValues';
import { SentenceRelated } from '@/components/features/japanese/related';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { DetailActionGroup, DetailActions } from '@/components/shared/DetailActions';
import { characterCount, DetailFacts } from '@/components/shared/DetailFacts';
import { DetailLayout } from '@/components/shared/DetailLayout';
import { Icon } from '@/components/shared/Icon';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { SavedListType } from '@/shared/constants/enums';

interface SentenceContentProps {
	sentence: MappedSentenceDetail;
}

const SOURCE_FACTS: Record<SentenceSource['kind'], string> = {
	tatoeba: 'Tatoeba',
	own: 'You',
	member: 'A member',
};

const SentenceContent = ({ sentence }: SentenceContentProps) => {
	const { isAuthenticated, user } = useAuth();
	const navigate = useNavigate();

	const deleteDialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(deleteDialogRef, { id: 'sentence-delete-modal' });

	const [deleteError, setDeleteError] = useState<string | null>(null);

	const deleteMutation = useDeleteSentenceMutation(sentence);

	// One gate for both controls, so imported sentences stay read-only for admins too.
	const canMutate = canMutateSentence(user, sentence);
	const isOwn = user != null && sentence.user_id === user.id;
	const source = sentenceSource(sentence, user?.id);

	const handleDelete = () => {
		setDeleteError(null);

		deleteMutation.mutate(undefined, {
			onSuccess: () => navigate('/sentences'),
			// Keep the modal open on failure — the sentence is still there.
			onError: (error) => setDeleteError(parseApiError(error).message),
		});
	};

	const facts = [
		{ term: 'Characters', value: characterCount(sentence.content) },
		{ term: 'Kanji', value: sentence.kanjis.length },
		...(source ? [{ term: 'Source', value: { text: SOURCE_FACTS[source.kind] } }] : []),
	];

	return (
		<>
			<DetailLayout
				railLabel="About this sentence"
				header={
					<EntryHeader backTo="/sentences" backLabel="Sentences" title={sentence.content} kind="sentence">
						{source ? <SentenceSourceLine source={source} /> : null}
					</EntryHeader>
				}
				main={deleteError ? <Alert tone="danger">{deleteError}</Alert> : null}
				facts={<DetailFacts title="About this sentence" facts={facts} />}
				actions={
					isAuthenticated || canMutate ? (
						<DetailActions>
							{isAuthenticated ? (
								<AuthorizedBookmarkWidget
									instanceObjectType={SavedListType.SENTENCES}
									isKnownType={SavedListType.KNOWNSENTENCES}
									entityId={sentence.id}
									modalTitle="Choose Sentence List to add"
									label="Save to a catalogue"
									savedLabel="Saved to a catalogue"
								/>
							) : null}
							{canMutate ? (
								<DetailActionGroup heading={isOwn ? 'Your sentence' : 'Manage sentence'}>
									<Button variant="outline" isFullWidth to={`/sentences/${sentence.uuid}/edit`}>
										<Icon size="sm" name="penSolid" />
										Edit sentence
									</Button>
									<Button
										variant="outline"
										isFullWidth
										onClick={deleteModal.open}
										aria-controls={deleteModal.id}
										aria-expanded={deleteModal.isOpen}
									>
										<Icon size="sm" name="trashbinSolid" />
										Delete sentence
									</Button>
								</DetailActionGroup>
							) : null}
						</DetailActions>
					) : undefined
				}
				after={<SentenceRelated sentence={sentence} />}
			/>

			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={sentence.content}
				ariaLabel="Delete sentence"
				deleteLabel="Yes, delete the sentence"
				onDelete={handleDelete}
				isProcessing={deleteMutation.isPending}
			/>
		</>
	);
};

export default SentenceContent;
