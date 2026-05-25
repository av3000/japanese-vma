import React, { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useRadicalQuery } from '@/api/radicals/details';
import {
	applyCatalogueForItemAction,
	type CatalogueForItem,
	type CatalogueForItemAction,
	fetchCataloguesForItem,
	updateCatalogueForItem,
} from '@/api/catalogues/cataloguesForItem';
import Spinner from '@/assets/images/spinner.gif';
import { CatalogueBookmarkModal } from '@/components/features/catalogues/CatalogueBookmarkModal';
import { useAuth } from '@/hooks/useAuth';
import { useModal } from '@/hooks/useModal';
import { ObjectTemplates } from '@/shared/constants';

const RadicalDetails: React.FC = () => {
	const [lists, setLists] = useState<CatalogueForItem[]>([]);
	const [radicalIsKnown, setRadicalIsKnown] = useState(false);
	const [listsAreLoading, setListsAreLoading] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState<number[]>([]);
	const bookmarkDialogRef = useRef<HTMLDialogElement | null>(null);

	const { radical_id } = useParams();
	const entityId = Number(radical_id);
	const navigate = useNavigate();
	const { isAuthenticated } = useAuth();
	const bookmarkModal = useModal(bookmarkDialogRef, { id: 'radical-bookmark-modal' });
	const { data: radical, isLoading: radicalIsLoading, isError } = useRadicalQuery(radical_id);

	useEffect(() => {
		if (!isAuthenticated || !radical_id) {
			setLists([]);
			setRadicalIsKnown(false);
			return;
		}

		const getUserRadicalLists = async () => {
			try {
				setListsAreLoading(true);
				const nextLists = await fetchCataloguesForItem(radical_id, {
					types: [ObjectTemplates.KNOWNRADICALS, ObjectTemplates.RADICALS],
				});
				setRadicalIsKnown(
					nextLists.some((list) => list.type === ObjectTemplates.KNOWNRADICALS && list.contains_item),
				);
				setLists(nextLists);
			} catch (error) {
				console.error(error);
			} finally {
				setListsAreLoading(false);
			}
		};

		void getUserRadicalLists();
	}, [isAuthenticated, radical_id]);

	const openBookmarkModal = () => {
		if (!isAuthenticated) {
			navigate('/login');
			return;
		}

		bookmarkModal.open();
	};

	const addToOrRemoveFromList = async (list: CatalogueForItem, action: CatalogueForItemAction) => {
		if (Number.isNaN(entityId)) return;

		try {
			setLoadingListIds((prev) => [...prev, list.id]);
			await updateCatalogueForItem({
				list,
				elementId: entityId,
				action,
			});

			setLists((prevLists) => {
				const nextLists = applyCatalogueForItemAction(prevLists, list.id, action);
				setRadicalIsKnown(
					nextLists.some((list) => list.type === ObjectTemplates.KNOWNRADICALS && list.contains_item),
				);
				return nextLists;
			});
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((loadingId) => loadingId !== list.id));
		}
	};

	if (radicalIsLoading || listsAreLoading) {
		return (
			<div className="container">
				<div className="row justify-content-center">
					<img src={Spinner} alt="spinner" />
				</div>
			</div>
		);
	}

	if (isError || !radical) {
		return (
			<div className="container">
				<div className="mt-5">
					<Link to="/radicals" className="tag-link">
						Back
					</Link>
				</div>
				<div className="row justify-content-center mt-5">
					<p>Unable to load radical.</p>
				</div>
			</div>
		);
	}

	return (
		<div className="container">
			<div className="mt-5">
				<Link to="/radicals" className="tag-link">
					Back
				</Link>
			</div>
			<div className="row justify-content-center mt-5">
				<div className="col-md-6">
					<h1>
						{radical.radical} <br />
						{radical.hiragana}
					</h1>
				</div>
				<div className="col-md-6">
					<p>meaning: {radical.meaning}</p>
					<p>strokes: {radical.strokes}</p>
					{radicalIsKnown && <i className="fas fa-check-circle text-success"> Learned</i>}
					<button
						onClick={openBookmarkModal}
						className="btn btn-outline brand-button float-right"
						aria-controls={bookmarkModal.id}
						aria-expanded={bookmarkModal.isOpen}
					>
						<i className="far fa-bookmark fa-lg"></i>
					</button>
				</div>
			</div>

			<hr />
			{radical.kanjis.length > 0 && (
				<>
					<h4>kanjis ({radical.kanjis.length}) results</h4>
					{radical.kanjis.map((kanji) => (
						<div className="row justify-content-center mt-5" key={kanji.uuid}>
							<div className="col-md-8">
								<div className="row justify-content-center">
									<div className="col-md-6">
										<h3>{kanji.character}</h3>
									</div>
									<div className="col-md-4">{kanji.meanings}</div>
									<div className="col-md-2">
										<Link to={`/kanji/${kanji.character}`} className="float-right">
											<i className="fas fa-external-link-alt fa-lg"></i>
										</Link>
									</div>
								</div>
								<hr />
							</div>
						</div>
					))}
				</>
			)}

			<CatalogueBookmarkModal
				controller={bookmarkModal}
				lists={lists}
				loadingListIds={loadingListIds}
				onListAction={addToOrRemoveFromList}
				title="Choose Radical List to add"
				emptyText="You have no radical lists created."
				ariaLabel="Save radical to list"
			/>
		</div>
	);
};

export default RadicalDetails;
