import { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { canMutateSentence, readSentenceWriteError, useDeleteSentenceMutation } from '@/api/sentences/authoring';
import type { MappedSentenceDetail } from '@/api/sentences/details';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { SavedListType } from '@/shared/constants/enums';

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
		<div className="container">
			<div className="mt-4">
				<Link to="/sentences" className="tag-link">Back</Link>
			</div>
			<div className="row justify-content-center mt-5">
				<div className="col-md-8">
					<h4>{sentence.content}</h4>
					{sentence.user_id ? (
						<p>User Author - {sentence.user_id}</p>
					) : (
						<p>
							Tatoeba link:{' '}
							<a href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`} target="_blank" rel="noopener noreferrer">
								{sentence.tatoeba_entry}
							</a>
						</p>
					)}
				</div>
				{canMutate && (
					<div className="d-flex align-items-start">
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
						<Link to={`/sentences/${sentence.uuid}/edit`} className="tag-link ml-2">
							Edit
						</Link>
					</div>
				)}
				{isAuthenticated && (
					<AuthorizedBookmarkWidget
						instanceObjectType={SavedListType.SENTENCES}
						isKnownType={SavedListType.KNOWNSENTENCES}
						entityId={sentence.id}
						modalTitle="Choose Sentence List to add"
					/>
				)}
			</div>
			{deleteError && <div className="row justify-content-center text-danger">{deleteError}</div>}
			<hr />
			<h4>Kanjis ({sentence.kanjis.length}) results</h4>
			<div className="container">
				{sentence.kanjis.map((kanji) => (
					<div className="row justify-content-center mt-5" key={kanji.uuid}>
						<div className="col-md-10">
							<div className="row">
								<div className="col-md-6"><h3>{kanji.character}</h3></div>
								<div className="col-md-4">{kanji.meanings.slice(0, 3).join(', ')}</div>
								<div className="col-md-2">
									<Link to={`/kanji/${kanji.uuid}`} className="float-right">Open</Link>
								</div>
							</div>
							<hr />
						</div>
					</div>
				))}
			</div>

			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={sentence.content}
				onDelete={handleDelete}
				isProcessing={deleteMutation.isPending}
			/>
		</div>
	);
};

export default SentenceContent;
