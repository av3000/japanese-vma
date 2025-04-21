// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
/* eslint-disable */
import React, { Component } from 'react';
import Spinner from '@/assets/images/spinner.gif';
import WordItem from '@/components/features/japanese/word/WordItem';
import { apiCall } from '@/services/api';
import { HTTP_METHOD } from '@/shared/constants';
import SearchBarWords from './SearchBarWords';

export class WordList extends Component {
	constructor() {
		super();
		this.state = {
			url: '/api/words',
			pagination: [],
			words: [],
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
		this.fetchWords(this.state.url);
	}

	fetchWords(givenUrl) {
		this.setState({ isLoading: true });
		return apiCall(HTTP_METHOD.GET, givenUrl)
			.then((res) => {
				const newState = Object.assign({}, this.state);
				newState.paginateObject = res.words;
				newState.words = [...newState.words, ...res.words.data];
				newState.url = res.words.next_page_url;

				newState.searchTotal = "results total: '" + res.words.total + "'";

				return newState;
			})
			.then((newState) => {
				newState.pagination = this.makePagination(newState.paginateObject);
				newState.isLoading = false;
				this.setState(newState);
			})
			.catch((err) => {
				console.log(err);
				this.setState({ isLoading: false });
			});
	}

	fetchQuery(queryParams) {
		this.setState({ isLoading: true });
		const newState = Object.assign({}, this.state);
		newState.filters = queryParams;
		apiCall('post', '/api/words/search', newState.filters)
			.then((res) => {
				if (res.success === true) {
					newState.paginateObject = res.words;
					newState.words = res.words.data ? res.words.data : newState.words;
					newState.url = res.words.next_page_url;

					newState.searchHeading = res.requestedQuery;
					newState.searchTotal = "Results total: '" + res.words.total + "'";
					newState.isLoading = false;
					return newState;
				}
			})
			.then((newState) => {
				newState.pagination = this.makePagination(newState.paginateObject);
				newState.isLoading = false;
				this.setState(newState);
			})
			.catch((err) => {
				this.setState(newState);
				console.log(err);
			});
	}

	fetchMoreQuery(givenUrl) {
		this.setState({ isLoading: true });
		const newState = Object.assign({}, this.state);
		apiCall('post', givenUrl, newState.filters)
			.then((res) => {
				console.log(res);

				newState.paginateObject = res.words;
				newState.words = [...newState.words, ...res.words.data];
				newState.url = res.words.next_page_url;

				newState.searchTotal = "Results total: '" + res.words.total + "'";
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
	}

	loadMore() {
		this.fetchWords(this.state.pagination.next_page_url);
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
		const { words, isLoading } = this.state;

		if (isLoading) {
			return (
				<div className="container text-center">
					<img src={Spinner} alt="Loading..." />
				</div>
			);
		}

		const wordList = words ? (
			words.map((w) => {
				// w.meaning = w.meaning.split("|")
				// w.meaning = w.meaning.slice(0, 3)
				// w.meaning = w.meaning.join(", ")

				return <WordItem key={w.id} id={w.id} {...w} addToList={this.addToList.bind(this, w.id)} />;
			})
		) : (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<img src={Spinner} alt="spinner" />
				</div>
			</div>
		);

		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<SearchBarWords fetchQuery={this.fetchQuery} />
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
						<div className="col-lg-8 col-md-10 mx-auto">{wordList}</div>
					</div>
				</div>
				<div className="row justify-content-center">
					{this.state.pagination.last_page === this.state.pagination.current_page ? (
						'no more results...'
					) : this.state.url.includes('search') ? (
						<button className="btn btn-outline-primary brand-button col-6" onClick={this.loadSearchMore}>
							Load More
						</button>
					) : (
						<button className="btn btn-outline-primary brand-button col-6" onClick={this.loadMore}>
							Load More
						</button>
					)}
				</div>
			</div>
		);
	}
}

export default WordList;
