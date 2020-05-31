import React, { Component } from 'react';
import { apiCall } from '../../services/api';
import { connect } from "react-redux";
import { hideLoader, showLoader } from "../../store/actions/application";

class ArticleEdit extends Component{
  constructor(props){
    super(props);
    this.state = {
        title_jp: "",
        title_en: "",
        content_en: "",
        content_jp: "",
        source_link: "",
        tags: "",
        publicity: false
    }

    this.handleChange = this.handleChange.bind(this);
    this.onSubmit = this.onSubmit.bind(this)
  }

  componentWillMount(){
    this.getArticleDetails();
  }

  getArticleDetails(){
    let articleId = this.props.match.params.article_id;
    return apiCall("get", `/api/article/${articleId}`)
        .then(res => {
            
            let tags = "";
            res.article.hashtags.map(tag => tags += tag.content + " " );
            console.log(tags);
            this.setState({
                title_jp: res.article.title_jp,
                content_jp: res.article.content_jp,
                source_link: res.article.source_link,
                tags: tags,
                publicity: res.article.publicity,
                old_title_jp: res.article.title_jp,
                old_content_jp: res.article.content_jp,
            }, () => {
                console.log(this.state);
            })
        })
        .catch(err => {
            console.log(err);
        });
    }

  onSubmit(e){
    e.preventDefault();
    let body = this.state.content_jp + this.state.title_jp;
    if(body.length < 4) {
        this.props.dispatch( showLoader("Fields are not filled properly!") );
        setTimeout(() => {
            this.props.dispatch( hideLoader() )
        }, 3000);

        return;
    }

    let digit = Math.ceil(body.length / 100) // 100chars = 1min
    let approxText = "It may take up to " + digit + " minutes."; 
    this.props.dispatch( showLoader("Creating Article, please wait.", approxText) );

    let payload = {
        title_jp: this.state.title_jp,
        content_jp: this.state.content_jp,
        source_link: this.state.source_link,
        tags: this.state.tags,
        reattach: 0
    };

    let oldBody = this.state.old_content_jp + this.state.old_title_jp;
    let newBody = this.state.content_jp + this.state.title_jp;

    if( oldBody.length !== newBody.length) {
        console.log("will take longer");
        payload.reattach = 1;
    }

    this.postNewArticle(payload);
  }

  postNewArticle(payload) {
    let articleId = this.props.match.params.article_id;
    return apiCall('put', `/api/article/${articleId}`, payload)
    .then(res => {
        console.log(res);
        console.log( {success: true, article: res.updated_article});
        this.props.dispatch( hideLoader() );
        this.props.history.push("/article/"+articleId);
    })
    .catch(err => {
        // let err = error.response.data.error;
        this.props.dispatch( hideLoader() );
        if(err.title_jp)
        {
            console.log(err.title_jp);
            // return err.title_jp[0];
            return {success: false, err: err.title_jp[0]};
        }
        else if(err.content_jp)
        {
            console.log(err.content_jp);
            // return err.content_jp[0];
            return {success: false, err: err.content_jp[0]};
        }
        else if(err.source_link)
        {
            console.log(err.source_link);
            // return err.source_link[0];
            return {success: false, err: err.source_link[0]};
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

  render(){
    return (

        <div className="container">
        <div className="row justify-content-md-center text-center">
            <form onSubmit={this.onSubmit} className="article-new-form col-md-10">
            {/* {this.props.errors.message && (
                <div className="alert alert-danger">{this.props.errors.message}</div>
            )} */}
            <label htmlFor="content_jp" className="mt-3"> <h4>Title</h4> </label>
            <input
                placeholder="Article title text"
                type="text"
                className="form-control"
                value={this.state.title_jp}
                name="title_jp"
                onChange={this.handleChange}
            />
            <label htmlFor="content_jp" className="mt-3"> <h4>Content</h4> </label>
            <textarea 
                placeholder="Article body text"
                type="text"
                className="form-control resize-none"
                value={this.state.content_jp}
                name="content_jp"
                onChange={this.handleChange}
                rows="7"
            ></textarea>
            <label htmlFor="content_jp" className="mt-3"> <h4>Source Link</h4> </label>
            <input
                placeholder="https://jplearning.online/article/title..."
                type="text"
                className="form-control"
                value={this.state.source_link}
                name="source_link"
                onChange={this.handleChange}
            />
            <label htmlFor="tags" className="mt-3"> <h4>Add Tags</h4> </label>
            <input
                placeholder="#movie #booktitle #office"
                type="text"
                className="form-control"
                value={this.state.tags}
                name="tags"
                onChange={this.handleChange}
            />
             <label htmlFor="publicity" className="mt-3">Publicity</label>
                <select name="publicity" value={this.state.publicity} className="form-control" onChange={this.handleChange}>
                    <option value="2">Public</option>
                    <option value="1">Private</option>
                    <option value="0">Draft</option>
                </select>
            <button type="submit" className="btn btn-outline-primary col-md-3 brand-button mt-5">
                Update the Article
            </button>
            </form>
        </div>
    </div>
    )
  }
}

const mapStateToProps = state => ({})

export default connect(mapStateToProps)(ArticleEdit);