// @ts-nocheck
/* eslint-disable */
/* eslint-disable */
import React, { Component } from 'react';
import { Button } from '@/components/shared/Button';
import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';
import { hideLoader, showLoader } from '@/store/actions/application';

class PostForm extends Component {
	constructor(props) {
		super(props);
		this.state = {
			title: '',
			content: '',
			tags: '',
			type: 1,
		};

		this.handleChange = this.handleChange.bind(this);
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
		return apiCall(HttpMethod.POST, `/api/post`, payload)
			.then((res) => {
				this.props.dispatch(hideLoader());
				this.props.history.push('/community/' + res.post.id);
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
		return (
			<div className="container">
				<div className="row justify-content-lg-center text-center">
					<form onSubmit={this.handleNewPost} className="col-12">
						{this.props.errors.message && (
							<div className="alert alert-danger">{this.props.errors.message}</div>
						)}
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
							Create Post
						</Button>
					</form>
				</div>
			</div>
		);
	}
}

export default PostForm;
