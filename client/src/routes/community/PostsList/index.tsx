// @ts-nocheck
/* eslint-disable */
import React, { Component } from 'react';
import Spinner from '@/assets/images/spinner.gif';
import SearchBar from '@/components/features/SearchBar';
import PostItem from '@/components/features/community/PostItem';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { apiCall } from '@/services/api';

export class PostsList extends Component {
	_isMounted = false;
	constructor(props) {
		super(props);
		this.state = {
			url: '/api/posts',
			pagination: [],
			posts: [],
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
		this.clearSearch = this.clearSearch.bind(this);
	}

	componentDidMount() {
		this._isMounted = true;
		this.fetchPosts(this.state.url);
	}

	clearSearch() {
		this.setState({ isLoading: true });
		return apiCall('get', '/api/posts')
			.then((res) => {
				if (this._isMounted) {
					const newState = Object.assign({}, this.state);
					newState.paginateObject = res.posts;
					newState.posts = res.posts.data;
					newState.url = res.posts.next_page_url;

					newState.searchHeading = '';
					newState.searchTotal = "results total: '" + res.posts.total + "'";
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
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	fetchPosts(givenUrl) {
		this.setState({ isLoading: true });
		return apiCall('get', givenUrl)
			.then((res) => {
				if (this._isMounted) {
					const newState = Object.assign({}, this.state);
					newState.paginateObject = res.posts;
					newState.posts = [...newState.posts, ...res.posts.data];
					newState.url = res.posts.next_page_url;

					newState.searchTotal = "results total: '" + res.posts.total + "'";
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
	}

	fetchQuery(queryParams) {
		this.setState({ isLoading: true });
		const newState = Object.assign({}, this.state);
		newState.filters = queryParams;
		apiCall('post', '/api/posts/search', newState.filters)
			.then((res) => {
				if (res.success === true) {
					newState.paginateObject = res.posts;
					newState.posts = res.posts.data ? res.posts.data : newState.posts;
					newState.url = res.posts.next_page_url;

					newState.searchHeading = res.requestedQuery;
					newState.searchTotal = "Results total: '" + res.posts.total + "'";

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
				newState.searchHeading = 'No results for tag: ' + newState.filters.title;
				newState.isLoading = false;
				this.setState(newState);
				console.log(err);
			});
	}

	fetchMoreQuery(givenUrl) {
		this.setState({ isLoading: true });
		const newState = Object.assign({}, this.state);
		apiCall('post', givenUrl, newState.filters)
			.then((res) => {
				newState.paginateObject = res.posts;
				newState.posts = [...newState.posts, ...res.posts.data];
				newState.url = res.posts.next_page_url;

				newState.searchHeading = "Requested query: '" + newState.filters.title + "'";
				newState.searchTotal = "Results total: '" + res.posts.total + "'";
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
	}

	loadMore() {
		this.fetchPosts(this.state.pagination.next_page_url);
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

	render() {
		const { posts, isLoading } = this.state;

		if (isLoading) {
			return (
				<div className="container text-center">
					<img src={Spinner} alt="Loading..." />
				</div>
			);
		}

		const postList = posts.map((w) => {
			return (
				<PostItem
					key={w.id}
					id={w.id}
					{...w}
					date={w.created_at}
					userId={w.user_id}
					hashtags={w.hashtags.slice(0, 3)}
					isLocked={w.locked}
				/>
			);
		});

		return (
			<div className="container mt-3">
				<div className="row justify-content-center">
					<SearchBar fetchQuery={this.fetchQuery} searchType="posts" />
				</div>
				<div className="mt-2">
					<div className="col-10">
						{this.state.searchHeading ? (
							<>
								<Button variant="ghost" onClick={this.clearSearch}>
									<Icon name="broomSolid" /> Clear search
								</Button>
								<br />
								<h4>{this.state.searchHeading}</h4>
							</>
						) : (
							''
						)}
						&nbsp;
						{this.state.searchTotal ? <h4>{this.state.searchTotal}</h4> : ''}
					</div>
					<div className="my-3 p-3 bg-white rounded box-shadow">
						<hr />
						<div className="col-lg-12 col-md-10 mx-auto">{postList}</div>
					</div>
				</div>
				<div className="row justify-content-center">
					{this.state.pagination.last_page === this.state.pagination.current_page ? (
						'no more results...'
					) : this.state.url.includes('search') ? (
						<Button variant="outline" className="w-50" onClick={this.loadSearchMore}>
							Load More
						</Button>
					) : (
						<Button variant="outline" className="w-50" onClick={this.loadMore}>
							Load More
						</Button>
					)}
				</div>
			</div>
		);
	}
}

export default PostsList;
