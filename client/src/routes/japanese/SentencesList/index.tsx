// @ts-nocheck
/* eslint-disable */
import React, { Component } from 'react';
import Spinner from '@/assets/images/spinner.gif';
import SentenceItem from '@/components/features/japanese/sentence/SentenceItem';
import { apiCall } from '@/services/api';
import { HTTP_METHOD } from '@/shared/constants';
import SearchBarSentences from './SearchBarSentences';

export class SentencesList extends Component {
	constructor() {
		super();
		this.state = {
			url: '/api/sentences',
			pagination: [],
			sentences: [],
			paginateObject: {},
			searchHeading: '',
			searchTotal: '',
			filters: [],
			isLoading: false,
		};

		this.loadMore = this.loadMore.bind(this);
		this.loadSearchMore = this.loadSearchMore.bind(this);
		this.fetchQuery = this.fetchQuery.bind(this);
		this.fetchMoreQuery = this.fetchMoreQuery.bind(this);
	}

	componentDidMount() {
		this.fetchSentences(this.state.url);
	}

	fetchSentences = async (givenUrl) => {
		this.setState({ isLoading: true });
		return apiCall(HTTP_METHOD.GET, givenUrl)
			.then((res) => {
				const newState = Object.assign({}, this.state);
				newState.paginateObject = res.sentences;
				newState.sentences = [...newState.sentences, ...res.sentences.data];
				newState.url = res.sentences.next_page_url;

				newState.searchTotal = "results total: '" + res.sentences.total + "'";
				newState.isLoading = false;
				return newState;
			})
			.then((newState) => {
				newState.pagination = this.makePagination(newState.paginateObject);
				newState.isLoading = false;
				this.setState(newState);
			})
			.catch((err) => {
				this.setState({ isLoading: false });
				console.log(err);
			});
	};

	fetchQuery = async (queryParams) => {
		this.setState({ isLoading: true });
		const newState = Object.assign({}, this.state);
		newState.filters = queryParams;
		apiCall('post', '/api/sentences/search', newState.filters)
			.then((res) => {
				if (res.success === true) {
					newState.paginateObject = res.sentences;
					newState.sentences = res.sentences.data ? res.sentences.data : newState.sentences;
					newState.url = res.sentences.next_page_url;

					newState.searchHeading = res.requestedQuery;
					newState.searchTotal = "Results total: '" + res.sentences.total + "'";
					newState.isLoading = false;
					return newState;
				}
			})
			.then((newState) => {
				this.setState((prevState) => ({
					...prevState,
					...newState,
					pagination: this.makePagination(newState.paginateObject),
					isLoading: false,
				}));
			})
			.catch((err) => {
				this.setState({ isLoading: false });
				console.log(err);
			});
	};

	fetchMoreQuery = async (givenUrl) => {
		this.setState({ isLoading: true });
		const newState = Object.assign({}, this.state);
		apiCall('post', givenUrl, newState.filters)
			.then((res) => {
				console.log(res);

				newState.paginateObject = res.sentences;
				newState.sentences = [...newState.sentences, ...res.sentences.data];
				newState.url = res.sentences.next_page_url;

				newState.searchTotal = "Results total: '" + res.sentences.total + "'";
				newState.isLoading = false;
				return newState;
			})
			.then((newState) => {
				this.setState((prevState) => ({
					...prevState,
					...newState,
					pagination: this.makePagination(newState.paginateObject),
					isLoading: false,
				}));
			})
			.catch((err) => {
				this.setState({ isLoading: false });
				console.log(err);
			});
	};

	loadMore() {
		this.fetchSentences(this.state.pagination.next_page_url);
	}

	loadSearchMore() {
		this.fetchMoreQuery(this.state.pagination.next_page_url);
	}

	makePagination(data) {
		return {
			current_page: data.current_page,
			last_page: data.last_page,
			next_page_url: data.next_page_url,
			prev_page_url: data.prev_page_url,
		};
	}

	addToList(id) {
		console.log('add to list: ' + id);
	}

	render() {
		const { sentences, isLoading } = this.state;

		if (isLoading) {
			return (
				<div className="container mt-5">
					<div className="row justify-content-center">
						<img src={Spinner} alt="Loading..." />
					</div>
				</div>
			);
		}

		const sentenceList = sentences.map((s) => {
			return (
				<SentenceItem
					key={s.id}
					id={s.id}
					tatoeba_entry={s.tatoeba_entry}
					userId={s.user_id}
					sentence={s.content}
					addToList={this.addToList.bind(this, s.id)}
				/>
			);
		});

		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<SearchBarSentences fetchQuery={this.fetchQuery} />
					{/* by tag */}
					{/* by keyword keyword */}
					{/* by newest/popular */}
				</div>
				<div className="container mt-5">
					<div className="row justify-content-center">
						{this.state.searchHeading ? <h4>{this.state.searchHeading}</h4> : ''}
						&nbsp;
						{this.state.searchTotal ? <h4>{this.state.searchTotal}</h4> : ''}
					</div>
					<div className="row">
						<div className="col-lg-8 col-md-10 mx-auto">{sentenceList}</div>
					</div>
				</div>
				<div className="row justify-content-center">
					{this.state.pagination.last_page === this.state.pagination.current_page ? (
						'no more results...'
					) : this.state.url.includes('search') ? (
						<button className="btn btn-outline-primary brand-button w-50" onClick={this.loadSearchMore}>
							Load More
						</button>
					) : (
						<button className="btn btn-outline-primary brand-button w-50" onClick={this.loadMore}>
							Load More
						</button>
					)}
				</div>
			</div>
		);
	}
}

export default SentencesList;
