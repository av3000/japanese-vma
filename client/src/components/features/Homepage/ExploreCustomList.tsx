// @ts-nocheck
/* eslint-disable */
import React, { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import Spinner from '@/assets/images/spinner.gif';
import { apiCall } from '@/services/api';
import { HTTP_METHOD } from '@/shared/constants';
import ExploreListItem from './ExploreListItem';

const ExploreCustomList: React.FC = () => {
	const [lists, setLists] = useState([]);
	const [totalLists, setTotalLists] = useState(null);
	const [isLoading, setIsLoading] = useState(false);
	const isMounted = useRef(true);

	// Equivalent to componentDidMount and componentWillUnmount
	useEffect(() => {
		isMounted.current = true;

		fetchLists();

		// Cleanup function (componentWillUnmount)
		return () => {
			isMounted.current = false;
		};
	}, []);

	const fetchLists = async () => {
		setIsLoading(true);
		try {
			const res = await apiCall(HTTP_METHOD.GET, '/api/lists');
			if (isMounted.current) {
				setTotalLists(res.lists.total);
				setLists(res.lists.data);
				setIsLoading(false);
			}
		} catch (err) {
			if (isMounted.current) {
				setIsLoading(false);
			}
			console.log(err);
		}
	};

	const listTypes = [
		'knownradicals list',
		'knownkanjis list',
		'knownwords list',
		'knownsentences list',
		'Radicals List',
		'Kanjis List',
		'Words List',
		'Sentences List',
		'Articles List',
	];

	if (isLoading) {
		return (
			<div className="d-flex justify-content-center w-100">
				<img src={Spinner} alt="spinner loading" />
			</div>
		);
	}

	const customLists = lists
		.slice(0, 3)
		.map((l) => (
			<ExploreListItem
				key={l.id}
				id={l.id}
				type={l.type}
				listType={listTypes[l.type - 1]}
				created_at={l.created_at}
				title={l.title}
				commentsTotal={l.commentsTotal}
				likesTotal={l.likesTotal}
				viewsTotal={l.viewsTotal}
				downloadsTotal={l.downloadsTotal}
				hashtags={l.hashtags.slice(0, 3)}
				itemsTotal={l.listItems.length}
				n1={l.n1}
				n2={l.n2}
				n3={l.n3}
				n4={l.n4}
				n5={l.n5}
			/>
		));

	return (
		<>
			<div className="d-flex justify-content-between align-items-center w-100 my-3">
				<h3>Latest Lists ({totalLists || 0})</h3>
				<div>
					<Link to="/lists" className="homepage-section-title">
						Read All Lists
					</Link>
				</div>
			</div>
			<div className="row">{customLists}</div>
		</>
	);
};

export default ExploreCustomList;
