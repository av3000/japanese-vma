import React, { Component } from 'react'
import axios from 'axios'
import { Link } from 'react-router-dom';
import { connect } from "react-redux";
import { apiCall } from '../../services/api';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import AvatarImg from '../../assets/images/avatar-woman.svg';
import { hideLoader, showLoader } from "../../store/actions/application";
import CommentList from '../comment/CommentList';
import CommentForm from '../comment/CommentForm';

class ArticleDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            article: null
        };

        this.likeArticle = this.likeArticle.bind(this);
        this.addComment = this.addComment.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
        this.downloadPdf = this.downloadPdf.bind(this);
    };
  
  componentDidMount(){
    let id = this.props.match.params.article_id;
    axios.get('/api/article/' + id)
      .then(res => {
        this.setState({
          article: res.data.article
        });
        // console.log(res.data.article.comments);
        return res.data.article;
      })
      .then(article => {
        if(!article)
        {
            this.props.history.push('/articles');
        }
        else if(this.props.currentUser.isAuthenticated)
        {
          return apiCall("post", `/api/article/${id}/checklike`)
            .then(res => { 
              let newState = Object.assign({}, this.state);
              newState.article.isLiked = res.isLiked;
              this.setState(newState);
            });
        }
      })
      .catch(err => {
          console.log(err);
      });
  };

  handleDelete(){
    console.log("deleted article: " + this.state.article.id);
    return apiCall("delete", `/api/article/${this.state.article.id}`)
      .then(res => { 
        console.log(res);
        this.props.history.push('/articles');
      })
      .catch(err => {
        console.log(err);
      });
      
  }

  addToList() {
      console.log("addToList");
  };

  downloadPdf() {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }
    else {
      this.props.dispatch( showLoader("Creating a PDF, please wait.") );

      let id = this.state.article.id;
      axios.get('/api/article/'+id+'/kanjis-pdf', {
        responseType: 'blob'
      })
      .then(res => {
        this.props.dispatch( hideLoader() );
        //Create a Blob from the PDF Stream
          const file = new Blob(
            [res.data], 
            {type: 'application/pdf'});
        //Build a URL from the file
          const fileURL = URL.createObjectURL(file);
        //Open the URL on new Window
          window.open(fileURL);
      })
      .catch(err => {
        console.log(err);
      })
    }

  };

  likeArticle() {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }

    let endpoint = this.state.article.isLiked === true ? "unlike" : "like";
    let id = this.state.article.id;

    axios.post('/api/article/'+id+'/'+endpoint)
      .then(res => {
        let newState = Object.assign({}, this.state);

        if(endpoint === "unlike"){
            newState.article.isLiked = !newState.article.isLiked;
            newState.article.likesTotal -= 1;
            this.setState(newState);
        }
        else if (endpoint === "like"){
            newState.article.isLiked = !newState.article.isLiked;
            newState.article.likesTotal += 1;
            this.setState(newState);
        }
      })
      .catch(err => {
          console.log(err);
      })
  };

  addComment(comment) {
      let newState = Object.assign({}, this.state);
      newState.article.comments.unshift(comment)
      this.setState({ newState });
  }

  deleteComment(commentId) {
    console.log("deleteComment");
  }

  editComment(commentId){
    console.log("editComment");
  }

  deleteComment(commentId){
    console.log("deleteComment");
  }

  render() {
    
    const { article } = this.state;
    const { currentUser } = this.props;
    // let comments = [];
    let comments = article ? article.comments : "";

    const singleArticle = article ? (

      <div className="container">
          <div className="row justify-content-center">
              <div className="col-lg-8 ">
                <h1 className="mt-4">{article.title_jp}</h1>
                <p className="text-muted"> 
                    Posted on {article.jp_year} {article.jp_month} {article.jp_day} {article.jp_hour}
                    <br/><span>{article.viewsTotal + 40} views</span>
                    <span className="mr-1 float-right d-flex">
                        {currentUser.user.id === article.user_id ? (
                          <i className="far fa-trash-alt fa-lg" onClick={this.handleDelete}></i>
                        ) : ""}
                        {currentUser.user.id === article.user_id ?
                          (<Link to={`/article/edit/${article.id}`}><i className="far fa-edit ml-3 fa-lg"></i></Link>) : ""
                        }
                        <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg"></i>
                        {/* { isBookmarked ? (<i class="fas fa-bookmark"></i>) } */}
                        <i onClick={this.downloadPdf} className="fas fa-file-download ml-3 fa-lg"></i>
                    </span>
                </p>
                <img className="img-fluid rounded mb-3" src={DefaultArticleImg} alt="default-article-img"/>
                <p className="lead">{article.content_jp} </p>
                <br/>
                <p>
                    {article.hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)}
                    <span className="mr-1 float-right d-flex text-muted">
                    {article.likesTotal+24} likes &nbsp;
                       {article.isLiked ? (<i onClick={this.likeArticle} className="fas fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       : (<i onClick={this.likeArticle} className="far fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       }
                    </span>
                </p>
                <hr/>
                <div className="text-muted">
                    <div className="mr-1 float-left d-flex">
                        <img src={AvatarImg} alt="book-japanese"/>
                        <p className="ml-3 mt-3">
                            uploaded by {article.userName}
                        </p>
                    </div>
                    <div className="mr-1 mt-3 float-right d-flex">
                        <Link to={article.source_link}> original source</Link>
                    </div>
                </div>
              </div>
          </div>
      </div>
    ) : (
      <div className="center">Loading article...</div>
    );

    return (
      <div className="container">
        {singleArticle}
        <br/>
        <div className="row justify-content-center">
              { currentUser.isAuthenticated && article ? (
                <div className="col-lg-8 pt-3 border-right">
                <hr/>
                  <h6>Share what's on your mind</h6>
                  <CommentForm addComment={this.addComment} articleId={this.state.article.id}/>
                </div>
              ) : ( 
                <div className="col-lg-8 pt-3 border-right">
                <hr/>
                  <h6>You need to 
                  <Link to="/login"> login </Link>
                  to comment</h6> 
                </div>
              )}
              <div className="col-lg-8 pt-3 bg-white">
                {comments ? (
                  <CommentList 
                    comments={comments}
                    deleteComment={this.deleteComment}
                    editComment={this.editComment}
                    />) : ("empty comments components")}
              </div>
        </div>
      </div>
    )
  }
}
const mapStateToProps = state => ({})

export default connect(mapStateToProps)(ArticleDetails);