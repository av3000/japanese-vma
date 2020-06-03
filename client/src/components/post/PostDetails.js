import React, { Component } from 'react'
import axios from 'axios'
import { Link } from 'react-router-dom';
import { connect } from "react-redux";
import { apiCall } from '../../services/api';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import AvatarImg from '../../assets/images/avatar-woman.svg';
import Spinner from '../../assets/images/spinner.gif';
import { hideLoader, showLoader } from "../../store/actions/application";
import CommentList from '../comment/CommentList';
import CommentForm from '../comment/CommentForm';

class PostDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            post: null
        };

        this.likePost = this.likePost.bind(this);
        this.addComment = this.addComment.bind(this);
        this.deleteComment = this.deleteComment.bind(this);
        this.likeComment = this.likeComment.bind(this);
        this.editComment = this.editComment.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
    };

  componentDidMount(){
    let id = this.props.match.params.post_id;
    axios.get('/api/post/' + id)
      .then(res => {
        this.setState({
          post: res.data.post
        });

        return res.data.post;
      })
      .then(post => {
        if(!post)
        {
            this.props.history.push('/community');
        }
        else if(this.props.currentUser.isAuthenticated)
        {
          return apiCall("post", `/api/post/${id}/checklike`)
            .then(res => { 
              let newState = Object.assign({}, this.state);
              newState.post.isLiked = res.isLiked;
              this.setState(newState);
            });
        }
      })
      .then( res => {
          let newState = Object.assign({}, this.state);
          if(this.props.currentUser.isAuthenticated)
          {
            newState.post.comments.map(comment => {
              let temp = comment.likes.find(like => like.user_id === this.props.currentUser.user.id)
              if(temp) { comment.isLiked = true}
              else { comment.isLiked = false}
          })

          this.setState(newState);
          }
      })
      .catch(err => {
          console.log(err);
      });

  };

  handleDelete(){
    return apiCall("delete", `/api/post/${this.state.post.id}`)
      .then(res => { 
        this.props.history.push('/community');
      })
      .catch(err => {
        console.log(err);
      });
  }


  likePost() {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }

    let endpoint = this.state.post.isLiked === true ? "unlike" : "like";
    let id = this.state.post.id;

    axios.post('/api/post/'+id+'/'+endpoint)
      .then(res => {
        let newState = Object.assign({}, this.state);

        if(endpoint === "unlike"){
            newState.post.isLiked = !newState.post.isLiked;
            newState.post.likesTotal -= 1;
            this.setState(newState);
        }
        else if (endpoint === "like"){
            newState.post.isLiked = !newState.post.isLiked;
            newState.post.likesTotal += 1;
            this.setState(newState);
        }
      })
      .catch(err => {
          console.log(err);
      })
  };

  likeComment(commentId) {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }

    let theComment = this.state.post.comments.find(comment => comment.id === commentId)

    let endpoint = theComment.isLiked === true ? "unlike" : "like";

    axios.post('/api/post/'+this.state.post.id+'/comment/'+commentId+'/'+endpoint)
      .then(res => {
        let newState = Object.assign({}, this.state);
        let index = this.state.post.comments.findIndex(comment => comment.id === commentId)
        newState.post.comments[index].isLiked = !newState.post.comments[index].isLiked
        
        if(endpoint === "unlike"){
            newState.post.comments[index].likesTotal -= 1;
        }
        else if (endpoint === "like"){
          newState.post.comments[index].likesTotal += 1;
        }

        this.setState(newState);
      })
      .catch(err => {
          console.log(err);
      })

  }

  addComment(comment) {
      let newState = Object.assign({}, this.state);
      newState.post.comments.unshift(comment)
      this.setState( newState );
  }

  deleteComment(commentId) {
    return apiCall("delete", `/api/post/${this.state.post.id}/comment/${commentId}`)
      .then(res => { 
        // console.log(res);
        // let newState = Object.assign({}, this.state);
        // newState.post.comments.filter(comment => comment.id !== commentId);
        // this.setState( newState );
        window.location.reload(false);
      })
      .catch(err => {
        console.log(err);
      });

  }

  editComment(commentId){
    console.log("editComment");
    console.log(commentId);
  }

  render() {
    
    const { post } = this.state;
    const { currentUser } = this.props;
    let comments = post ? post.comments : "";

    const singlePost = post ? (
      <div className="container">
          <div className="row justify-content-center">
              <div className="col-lg-8 ">
                <span className="row mt-4">
                  <Link to="/community" className="tag-link">Back</Link>
                </span>
                <h1 className="mt-4">{post.title_jp}</h1>
                <p className="text-muted"> 
                    Posted on {post.jp_year} {post.jp_month} {post.jp_day} {post.jp_hour}
                    <br/><span>{post.viewsTotal + 40} views</span>
                    {currentUser.user.id === post.user_id ? (post.publicity === 1 ? " | public" : " | private" ) : ""}
                    <span className="mr-1 float-right d-flex">
                        {currentUser.user.id === post.user_id ? (
                          <i className="far fa-trash-alt fa-lg" onClick={this.handleDelete}></i>
                        ) : ""}
                        {currentUser.user.id === post.user_id ?
                          (<Link to={`/post/edit/${post.id}`}><i className="far fa-edit ml-3 fa-lg"></i></Link>) : ""
                        }
                        <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg"></i>
                        {/* { isBookmarked ? (<i class="fas fa-bookmark"></i>) } */}
                        <i onClick={this.downloadPdf} className="fas fa-file-download ml-3 fa-lg"></i>
                    </span>
                </p>
                <img className="img-fluid rounded mb-3" src={DefaultArticleImg} alt="default-article-img"/>
                <p className="lead">{post.content_jp} </p>
                <br/>
                <p>
                    {post.hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)}
                    <span className="mr-1 float-right d-flex text-muted">
                    {post.likesTotal+24} likes &nbsp;
                       {post.isLiked ? (<i onClick={this.likePost} className="fas fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       : (<i onClick={this.likePost} className="far fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       }
                    </span>
                </p>
                <hr/>
                <div className="text-muted">
                    <div className="mr-1 float-left d-flex">
                        <img src={AvatarImg} alt="book-japanese"/>
                        <p className="ml-3 mt-3">
                            uploaded by {post.userName}
                        </p>
                    </div>
                    <div className="mr-1 mt-3 float-right d-flex">
                        <Link to={post.source_link}> original source</Link>
                    </div>
                </div>
              </div>
          </div>
      </div>
    ) : "";

    return (
      <div className="container">
        {singlePost ? singlePost : (
          <div className="container">
              <div className="row justify-content-center">
                  <img src={Spinner}/>
              </div>
          </div>
        )}
        <br/>
        <div className="row justify-content-center">
              { post ? (currentUser.isAuthenticated ?(
                <div className="col-lg-8">
                <hr/>
                  <h6>Share what's on your mind</h6>
                  <CommentForm 
                    addComment={this.addComment}
                    currentUser={currentUser} 
                    objectId={this.state.post.id}
                    objectType="post"
                    />
                </div>
                )
                : (
                  <div className="col-lg-8">
                  <hr/>
                    <h6>You need to 
                    <Link to="/login"> login </Link>
                    to comment</h6> 
                  </div>
                )
              ) 
              : ""
              
              }
              <div className="col-lg-8">
                {comments ? (
                  <CommentList 
                    objectId={this.state.post.id}
                    currentUser={currentUser}
                    comments={comments}
                    deleteComment={this.deleteComment}
                    likeComment={this.likeComment}
                    editComment={this.editComment}
                    />) : (
                      <div className="container">
                        <div className="row justify-content-center">
                            <img src={Spinner}/>
                        </div>
                    </div>
                    )}
              </div>
        </div>
      </div>
    )
  }
}
const mapStateToProps = state => ({})

export default connect(mapStateToProps)(PostDetails);