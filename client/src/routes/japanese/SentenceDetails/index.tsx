import React, { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
	applyCatalogueForItemAction,
	type CatalogueForItem,
	type CatalogueForItemAction,
	fetchCataloguesForItem,
	updateCatalogueForItem,
} from '@/api/catalogues/cataloguesForItem';
import { useSentenceQuery } from '@/api/sentences/details';
import Spinner from '@/assets/images/spinner.gif';
import { CatalogueBookmarkModal } from '@/components/features/catalogues/CatalogueBookmarkModal';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { ObjectTemplates } from '@/shared/constants';

const getKanjiMeanings = (kanji: { meanings?: string | string[]; meaning?: string }) => {
	if (Array.isArray(kanji.meanings)) {
		return kanji.meanings.slice(0, 3).join(', ');
	}

	return (kanji.meanings ?? kanji.meaning ?? '').split('|').slice(0, 3).join(', ');
};

const SentenceDetails: React.FC = () => {
	const [lists, setLists] = useState<CatalogueForItem[]>([]);
	const [sentenceIsKnown, setSentenceIsKnown] = useState(false);
	const [isLoadingLists, setIsLoadingLists] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState<number[]>([]);
	const bookmarkDialogRef = useRef<HTMLDialogElement | null>(null);
	const bookmarkModal = useModal(bookmarkDialogRef, { id: 'sentence-bookmark-modal' });

	const { sentence_id } = useParams();
	const entityId = Number(sentence_id);
	const navigate = useNavigate();
	const { isAuthenticated } = useAuth();
	const sentenceQuery = useSentenceQuery(sentence_id);
	const sentence = sentenceQuery.data;

	useEffect(() => {
		if (isAuthenticated) {
			void getUserSentenceLists();
		}
	}, [isAuthenticated, sentence_id]);

	const getUserSentenceLists = async () => {
		if (!sentence_id) {
			return;
		}

		setIsLoadingLists(true);
		try {
			const nextLists = await fetchCataloguesForItem(sentence_id, {
				types: [ObjectTemplates.KNOWNSENTENCES, ObjectTemplates.SENTENCES],
			});
			setSentenceIsKnown(
				nextLists.some((list) => list.type === ObjectTemplates.KNOWNSENTENCES && list.contains_item),
			);
			setLists(nextLists);
		} catch (error) {
			console.error(error);
		} finally {
			setIsLoadingLists(false);
		}
	};

	const toggleModal = () => {
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		bookmarkModal.open();
	};

	const addToOrRemoveFromList = async (list: CatalogueForItem, action: CatalogueForItemAction) => {
		if (Number.isNaN(entityId)) {
			return;
		}

		setLoadingListIds((prev) => [...prev, list.id]);
		try {
			await updateCatalogueForItem({
				list,
				elementId: entityId,
				action,
			});

			setLists((prevLists) => {
				const nextLists = applyCatalogueForItemAction(prevLists, list.id, action);
				setSentenceIsKnown(
					nextLists.some((list) => list.type === ObjectTemplates.KNOWNSENTENCES && list.contains_item),
				);
				return nextLists;
			});
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((id) => id !== list.id));
		}
	};

	if (sentenceQuery.isLoading || isLoadingLists) {
		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<img src={Spinner} alt="Loading..." />
				</div>
			</div>
		);
	}

	if (sentenceQuery.error || !sentence) {
		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<p>Sentence could not be loaded.</p>
				</div>
			</div>
		);
	}

	return (
		<div className="container">
			<span className="mt-4">
				<Link to="/sentences" className="tag-link">
					Back
				</Link>
			</span>

			<div className="row justify-content-center mt-5">
				<div className="col-md-8">
					<h4>{sentence.content}</h4>
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
				</div>
				<div className="col-md-4">
					{sentenceIsKnown && <i className="fas fa-check-circle text-success"> Learned</i>}
					<button
						onClick={toggleModal}
						className="btn btn-outline brand-button float-right"
						aria-controls={bookmarkModal.id}
						aria-expanded={bookmarkModal.isOpen}
					>
						<i className="far fa-bookmark fa-lg"></i>
					</button>
				</div>
			</div>

			<hr />

			<>
				<h4>Kanjis ({sentence.kanjis.length}) results</h4>
				<div className="container">
					{sentence.kanjis.map((kanji) => (
						<div className="row justify-content-center mt-5" key={kanji.uuid}>
							<div className="col-md-10">
								<div className="row">
									<div className="col-md-6">
										<h3>{kanji.character}</h3>
									</div>
									<div className="col-md-4">{getKanjiMeanings(kanji)}</div>
									<div className="col-md-2">
										<Link to={`/kanji/${kanji.uuid}`} className="float-right">
											<i className="fas fa-external-link-alt fa-lg"></i>
										</Link>
									</div>
								</div>
								<hr />
							</div>
						</div>
					))}
				</div>
			</>

			<hr />
			<br />

			<CatalogueBookmarkModal
				controller={bookmarkModal}
				lists={lists}
				loadingListIds={loadingListIds}
				onListAction={addToOrRemoveFromList}
				title="Choose Sentence List to add"
				ariaLabel="Choose Sentence List to add"
			/>
		</div>
	);
};

export default SentenceDetails;
