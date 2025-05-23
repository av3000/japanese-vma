// @ts-nocheck
/* eslint-disable */
import React, { Component } from 'react';
import Spinner from '@/assets/images/spinner.gif';
import RadicalItem from '@/components/features/japanese/radical/RadicalItem';
import { apiCall } from '@/services/api';
import { HTTP_METHOD } from '@/shared/constants';
import SearchBarRadicals from './SearchBarRadicals';

export class RadicalList extends Component {
	constructor() {
		super();
		this.state = {
			url: '/api/radicals',
			pagination: [],
			radicals: [],
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
		this.fetchRadicals(this.state.url);
	}

	fetchRadicals(givenUrl) {
		this.setState({ isLoading: true });
		return apiCall(HTTP_METHOD.GET, givenUrl)
			.then((res) => {
				const newState = Object.assign({}, this.state);
				newState.paginateObject = res.radicals;
				newState.radicals = [...newState.radicals, ...res.radicals.data];
				newState.url = res.radicals.next_page_url;

				newState.searchTotal = "results total: '" + res.radicals.total + "'";

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
	}

	fetchQuery(queryParams) {
		this.setState({ isLoading: true });
		const newState = Object.assign({}, this.state);
		newState.filters = queryParams;
		apiCall('post', '/api/radicals/search', newState.filters)
			.then((res) => {
				if (res.success === true) {
					newState.paginateObject = res.radicals;
					newState.radicals = res.radicals.data ? res.radicals.data : newState.radicals;
					newState.url = res.radicals.next_page_url;

					newState.searchHeading = res.requestedQuery;
					newState.searchTotal = "Results total: '" + res.radicals.total + "'";
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
				this.setState((prevState) => ({
					...prevState,
					searchHeading: 'No results for tag: ' + newState.filters.keyword,
					isLoading: false,
				}));
				console.log(err);
			});
	}

	fetchMoreQuery(givenUrl) {
		this.setState({ isLoading: true });
		const newState = Object.assign({}, this.state);
		apiCall('post', givenUrl, newState.filters)
			.then((res) => {
				newState.paginateObject = res.radicals;
				newState.radicals = [...newState.radicals, ...res.radicals.data];
				newState.url = res.radicals.next_page_url;

				newState.searchTotal = "Results total: '" + res.radicals.total + "'";

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
	}

	loadMore() {
		this.fetchRadicals(this.state.pagination.next_page_url);
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
		const { radicals, isLoading } = this.state;

		if (isLoading) {
			return (
				<div className="container text-center">
					<img src={Spinner} alt="Loading..." />
				</div>
			);
		}

		const radicalList = radicals.map((r) => (
			<RadicalItem key={r.id} id={r.id} {...r} addToList={this.addToList.bind(this, r.id)} />
		));

		return (
			<div className="container mt-5">
				<div className="row justify-content-center">
					<SearchBarRadicals fetchQuery={this.fetchQuery} />
				</div>
				<div className="container mt-5">
					<div className="row justify-content-center">
						{this.state.searchHeading ? <h4>{this.state.searchHeading}</h4> : ''}
						&nbsp;
						{this.state.searchTotal ? <h4>{this.state.searchTotal}</h4> : ''}
					</div>
					<div className="row">
						<div className="col-lg-8 col-md-10 mx-auto">{radicalList}</div>
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

export default RadicalList;
