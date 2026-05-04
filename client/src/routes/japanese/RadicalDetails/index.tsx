// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useState } from 'react';
import { Button, Modal } from 'react-bootstrap';
import { useSelector } from 'react-redux';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
	applyCatalogueMembershipAction,
	type CatalogueBookmarkListItem,
	type CatalogueMembershipAction,
	fetchElementCatalogueMembership,
	filterCatalogueMembershipByType,
	updateElementCatalogueMembership,
} from '@/api/catalogues/bookmarkMembership';
import Spinner from '@/assets/images/spinner.gif';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { BASE_URL, ObjectTemplates } from '@/shared/constants';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import { HttpMethod } from '@/shared/types';

const RadicalDetails: React.FC = () => {
	const [radical, setRadical] = useState({});
	const [lists, setLists] = useState([]);
	const [showModal, setShowModal] = useState(false);
	const [radicalIsKnown, setRadicalIsKnown] = useState(false);
	const [isLoading, setIsLoading] = useState(false);
	const [loadingListIds, setLoadingListIds] = useState([]);

	const { radical_id } = useParams();
	const entityId = Number(radical_id);
	const navigate = useNavigate();
	const { isAuthenticated } = useAuth();

	useEffect(() => {
		getRadicalDetails();
		if (isAuthenticated) {
			getUserRadicalLists();
		}
	}, [isAuthenticated]);

	const getRadicalDetails = async () => {
		try {
			setIsLoading(true);
			const res = await apiCall(HttpMethod.GET, `${BASE_URL}/api/radical/${radical_id}`);
			setRadical(res);
		} catch (error) {
			console.error(error);
		} finally {
			setIsLoading(false);
		}
	};

	const getUserRadicalLists = async () => {
		try {
			setIsLoading(true);
			const userLists = await fetchElementCatalogueMembership(radical_id);
			const nextLists = filterCatalogueMembershipByType(userLists, [
				ObjectTemplates.KNOWNRADICALS,
				ObjectTemplates.RADICALS,
			]);
			setRadicalIsKnown(
				nextLists.some((list) => list.type === ObjectTemplates.KNOWNRADICALS && list.elementBelongsToList),
			);
			setLists(nextLists);
		} catch (error) {
			console.error(error);
		} finally {
			setIsLoading(false);
		}
	};

	const toggleModal = () => {
		if (!isAuthenticated) {
			navigate('/login');
		} else {
			setShowModal((prevShow) => !prevShow);
		}
	};

	const addToOrRemoveFromList = async (list: CatalogueBookmarkListItem, action: CatalogueMembershipAction) => {
		if (Number.isNaN(entityId)) return;

		try {
			setLoadingListIds((prev) => [...prev, list.id]);
			await updateElementCatalogueMembership({
				list,
				elementId: entityId,
				action,
			});

			setLists((prevLists) => {
				const nextLists = applyCatalogueMembershipAction(prevLists, list.id, action);
				setRadicalIsKnown(
					nextLists.some(
						(list) => list.type === ObjectTemplates.KNOWNRADICALS && list.elementBelongsToList,
					),
				);
				return nextLists;
			});
		} catch (error) {
			console.error(error);
		} finally {
			setLoadingListIds((prev) => prev.filter((loadingId) => loadingId !== list.id));
		}
	};

	return (
		<div className="container">
			<div className="mt-5">
				<Link to="/radicals" className="tag-link">
					Back
				</Link>
			</div>
			{isLoading ? (
				<div className="container">
					<div className="row justify-content-center">
						<img src={Spinner} alt="spinner" />
					</div>
				</div>
			) : (
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
							onClick={toggleModal}
							className="btn btn-outline brand-button float-right"
							variant="outline-primary"
						>
							<i className="far fa-bookmark fa-lg"></i>
						</button>
					</div>
				</div>
			)}

			<hr />
			{radical.kanjis && radical.kanjis.length > 0 && (
				<>
					<h4>kanjis ({radical.kanjis.length}) results</h4>
					{radical.kanjis.map((kanji) => (
						<div className="row justify-content-center mt-5" key={kanji.id}>
							<div className="col-md-8">
								<div className="row justify-content-center">
									<div className="col-md-6">
										<h3>{kanji.kanji}</h3>
									</div>
									<div className="col-md-4">{kanji.meaning}</div>
									<div className="col-md-2">
										<Link to={`/kanji/${kanji.id}`} className="float-right">
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

			<Modal show={showModal} onHide={toggleModal}>
				<Modal.Header closeButton>
					<Modal.Title>Choose Radical List to add</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					{lists.map((list) => (
						<div key={list.id} className="d-flex justify-content-between">
							<Link to={CATALOGUE_ROUTES.detail(list.uuid)}>{list.title}</Link>
							<Button
								variant={list.elementBelongsToList ? 'danger' : 'primary'}
								size="sm"
								onClick={() => addToOrRemoveFromList(list, list.elementBelongsToList ? 'remove' : 'add')}
								disabled={loadingListIds.includes(list.id)}
							>
								{loadingListIds.includes(list.id) ? (
									<span className="spinner-border spinner-border-sm"></span>
								) : list.elementBelongsToList ? (
									'Remove'
								) : (
									'Add'
								)}
							</Button>
						</div>
					))}
					<small>
						<Link to={CATALOGUE_ROUTES.create}>Create a new list?</Link>
					</small>
				</Modal.Body>
				<Modal.Footer>
					<Button variant="secondary" onClick={toggleModal}>
						Close
					</Button>
				</Modal.Footer>
			</Modal>
		</div>
	);
};

export default RadicalDetails;
