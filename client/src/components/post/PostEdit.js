import React, { Component } from 'react';
import { apiCall } from '../../services/api';
import { connect } from "react-redux";
import { hideLoader, showLoader } from "../../store/actions/application";

class PostEdit extends Component{
    constructor(props) {
        super(props);
        this.state = {
            title: "",
            content: "",
            tags: "",
            type: 1
        };
    
        this.handleChange = this.handleChange.bind(this);
    }

  componentWillMount(){
    this.getPostDetails();
  }

  getPostDetails(){
    let postId = this.props.match.params.post_id;
    return apiCall("get", `/api/post/${postId}`)
        .then(res => {
            
            let tags = "";
            res.post.hashtags.map(tag => tags += tag.content + " " );
            this.setState({
                title: res.post.title,
                content: res.post.content,
                type: res.post.type,
                tags: tags
            }, () => {
                // console.log(this.state);
            })
        })
        .catch(err => {
            console.log(err);
        });
    }

    handleNewPost = e => {
        e.preventDefault();
    
        let body = this.state.content + this.state.title;
        if(body.length < 4) {
            this.props.dispatch( showLoader("Fields are not filled properly!") );
            setTimeout(() => {
                this.props.dispatch( hideLoader() )
            }, 3000);
    
            return;
        }
    
        this.props.dispatch( showLoader("Creating Post, please wait.", " It may take a few seconds.") );
    
        let payload = {
            title: this.state.title,
            content: this.state.content,
            tags: this.state.tags,
            type: this.state.type
        };
    
        this.postNewPost(payload);
      }
    
      postNewPost(payload) {
        let postId = this.props.match.params.post_id;
        return apiCall("put", `/api/post/${postId}`, payload)
        .then(res => {
            console.log(res);
            this.props.dispatch( hideLoader() );
            this.props.history.push("/community/"+res.updatedPost.id);
        })
        .catch(err => {
            // let err = error.response.data.error;
            this.props.dispatch( hideLoader() );
            if(err.title)
            {
                console.log(err);
                // return err.title_jp[0];
                return {success: false, err: err.title};
            }
            else if(err.content)
            {
                console.log(err.content);
                // return err.content_jp[0];
                return {success: false, err: err.content[0]};
            }
            else {
                console.log(err);
                return {success: false, err};
            }
        });
      }
    
      handleChange(e){
        this.setState({ [e.target.name]: e.target.value });
      }
    
      render() {
        return (
            <div className="container">
                <div className="row justify-content-lg-center text-center">
                    <form onSubmit={this.handleNewPost} className="col-12">
                    {/* {this.props.errors.message && (
                        <div className="alert alert-danger">{this.props.errors.message}</div>
                    )} */}
                    <label htmlFor="title" className="mt-3"> <h4>Title</h4> </label>
                    <input
                        placeholder="Post title text"
                        type="text"
                        className="form-control"
                        value={this.state.title}
                        name="title"
                        onChange={this.handleChange}
                    />
                    <label htmlFor="content" className="mt-3"> <h4>Content</h4> </label>
                    <textarea 
                        placeholder="Post body text"
                        type="text"
                        className="form-control resize-none"
                        value={this.state.content}
                        name="content"
                        onChange={this.handleChange}
                        rows="7"
                    ></textarea>
                    <label htmlFor="tags" className="mt-3"> <h4>Add Tags</h4> </label>
                    <input
                        placeholder="#uimistake #suggestion #howto"
                        type="text"
                        className="form-control"
                        value={this.state.tags}
                        name="tags"
                        onChange={this.handleChange}
                    />
                    <label htmlFor="type" className="mt-3">Topic</label>
                    <select name="type" value={this.state.type} className="form-control" onChange={this.handleChange}>
                        <option value="1">Content-related</option>
                        <option value="2">Off-topic</option>
                        <option value="3">FAQ</option>
                        <option value="4">Technical</option>
                        <option value="5">Bug</option>
                        <option value="6">Feedback</option>
                        <option value="7">Announcement</option>
                    </select>
                    <button type="submit" className="btn btn-outline-primary col-md-3 brand-button mt-5">
                        Update Post
                    </button>
                    </form>
                </div>
            </div>
        );
      }
    }
    
    const mapStateToProps = state => ({})
    
    export default connect(mapStateToProps)(PostEdit);