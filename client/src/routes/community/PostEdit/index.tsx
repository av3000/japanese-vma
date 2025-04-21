// @ts-nocheck
/* eslint-disable */
import React, { Component } from 'react';
import Spinner from '@/assets/images/spinner.gif';
import { Button } from '@/components/shared/Button';
import { apiCall } from '@/services/api';
import { hideLoader, showLoader } from '@/store/actions/application';

class PostEdit extends Component {
	constructor(props) {
		super(props);
		this.state = {
			title: '',
			content: '',
			tags: '',
			type: 1,
			isLoading: false,
		};

		this.handleChange = this.handleChange.bind(this);
	}

	componentWillMount() {
		this.getPostDetails();
	}

	getPostDetails() {
		this.setState({ isLoading: true });
		const postId = this.props.match.params.post_id;
		return apiCall('get', `/api/post/${postId}`)
			.then((res) => {
				let tags = '';
				res.post.hashtags.map((tag) => (tags += tag.content + ' '));
				this.setState({
					title: res.post.title,
					content: res.post.content,
					type: res.post.type,
					tags: tags,
					isLoading: false,
				});
			})
			.catch((err) => {
				this.setState({ isLoading: false });
				console.log(err);
			});
	}

	handleNewPost = (e) => {
		e.preventDefault();

		const body = this.state.content + this.state.title;
		if (body.length < 4) {
			this.props.dispatch(showLoader('Fields are not filled properly!'));
			setTimeout(() => {
				this.props.dispatch(hideLoader());
			}, 3000);

			return;
		}

		this.props.dispatch(showLoader('Creating Post, please wait.', ' It may take a few seconds.'));

		const payload = {
			title: this.state.title,
			content: this.state.content,
			tags: this.state.tags,
			type: this.state.type,
		};

		this.postNewPost(payload);
	};

	postNewPost(payload) {
		const postId = this.props.match.params.post_id;
		return apiCall('put', `/api/post/${postId}`, payload)
			.then((res) => {
				this.props.dispatch(hideLoader());
				this.props.history.push('/community/' + res.updatedPost.id);
			})
			.catch((err) => {
				this.props.dispatch(hideLoader());
				if (err.title) {
					return { success: false, err: err.title };
				} else if (err.content) {
					return { success: false, err: err.content[0] };
				} else {
					console.log(err);
					return { success: false, err };
				}
			});
	}

	handleChange(e) {
		this.setState({ [e.target.name]: e.target.value });
	}

	render() {
		const { isLoading } = this.state;

		if (isLoading) {
			return (
				<div className="d-flex justify-content-center w-100">
					<img src={Spinner} alt="spinner loading" />
				</div>
			);
		}

		return (
			<div className="container">
				<div className="row justify-content-lg-center text-center">
					<form onSubmit={this.handleNewPost} className="col-12">
						<label htmlFor="title" className="mt-3">
							{' '}
							<h4>Title</h4>{' '}
						</label>
						<input
							placeholder="Post title text"
							type="text"
							className="form-control"
							value={this.state.title}
							name="title"
							onChange={this.handleChange}
						/>
						<label htmlFor="content" className="mt-3">
							{' '}
							<h4>Content</h4>{' '}
						</label>
						<textarea
							placeholder="Post body text"
							type="text"
							className="form-control resize-none"
							value={this.state.content}
							name="content"
							onChange={this.handleChange}
							rows="7"
						></textarea>
						<label htmlFor="tags" className="mt-3">
							{' '}
							<h4>Add Tags</h4>{' '}
						</label>
						<input
							placeholder="#uimistake #suggestion #howto"
							type="text"
							className="form-control"
							value={this.state.tags}
							name="tags"
							onChange={this.handleChange}
						/>
						<label htmlFor="type" className="mt-3">
							Topic
						</label>
						<select
							name="type"
							value={this.state.type}
							className="form-control"
							onChange={this.handleChange}
						>
							<option value="1">Content-related</option>
							<option value="2">Off-topic</option>
							<option value="3">FAQ</option>
							<option value="4">Technical</option>
							<option value="5">Bug</option>
							<option value="6">Feedback</option>
							<option value="7">Announcement</option>
						</select>
						<Button type="submit" variant="primary">
							Update Post
						</Button>
					</form>
				</div>
			</div>
		);
	}
}

export default PostEdit;
