import React, { Component } from 'react';
import axios from 'axios';
import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import { connect } from "react-redux";
import { apiCall } from '../../services/api';
import AvatarImg from '../../assets/images/avatar-woman.svg';
import Spinner from '../../assets/images/spinner.gif';
import CommentList from '../comment/CommentList';
import CommentForm from '../comment/CommentForm';
import { Button, Modal } from 'react-bootstrap';

class PostDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            post: null,
            showDelete: false
        };

        this.likePost = this.likePost.bind(this);
        this.toggleLock = this.toggleLock.bind(this);
        this.addComment = this.addComment.bind(this);
        this.deleteComment = this.deleteComment.bind(this);
        this.likeComment = this.likeComment.bind(this);
        this.editComment = this.editComment.bind(this);
        this.deletePost = this.deletePost.bind(this);

        this.handleDeleteModalClose = this.handleDeleteModalClose.bind(this);
        this.openDeleteModal = this.openDeleteModal.bind(this);
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

  deletePost(){
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

  toggleLock(){
    if(!this.props.currentUser.isAuthenticated){
      this.props.history.push('/login');
    } else if( this.props.currentUser.user.isAdmin ) {
      let id = this.state.post.id;
      axios.post("/api/post/"+id+"/toggleLock")
        .then(res => {
          let newState = Object.assign({}, this.state);
          newState.post.locked = res.data.locked;
          this.setState( newState );
        })
        .catch(err => {
          console.log(err);
        })
    }
  }

// Modals
  handleDeleteModalClose(){
    console.log("handleDeleteModalClose");
      console.log(this.state.showDelete);
    this.setState({showDelete: !this.state.showDelete})
  }

  openDeleteModal() {
    if(this.props.currentUser.isAuthenticated === false){
      this.props.history.push('/login');
    }
    else {
      console.log("openDeleteModal");
      console.log(this.state.showDelete);
        this.setState({showDelete: !this.state.showDelete})
    }
  }

// Comments

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
        let newState = Object.assign({}, this.state);
        newState.post.comments = newState.post.comments.filter(comment => comment.id !== commentId);
        this.setState( newState );
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
                <h1 className="mt-4">{post.title}</h1>
                <p className="text-muted"> 
                    <Moment className="text-muted" format="Do MMM YYYY">
                      {post.created_at}
                    </Moment>
                    <br/>{post.viewsTotal + 40} views &nbsp;
                    <strong> {post.postType} </strong>
                </p>
                <ul className="brand-icons mr-1 d-flex">
                    {currentUser.user.id === post.user_id || currentUser.user.isAdmin ?
                      (
                      <li onClick={this.openDeleteModal}>
                        <button> 
                          <i className="far fa-trash-alt fa-lg"></i>
                        </button>
                      </li>) : ""
                    }
                    {currentUser.user.id === post.user_id ?
                      (<Link to={`/community/edit/${post.id}`}> 
                      <li>
                        <button> 
                          <i className="fas fa-pen-alt fa-lg"></i>
                        </button>
                      </li>
                      </Link>) : ("")
                    }
                    {
                      currentUser.user.isAdmin ? (
                        <li onClick={this.toggleLock}>
                          <button>
                              { post.locked === 1 ? (
                                <i className="fas fa-lock-open"></i>
                              ) : (
                                <i className="fas fa-lock"></i>
                              )}
                          </button>
                        </li>
                      ) : ""
                    }
                </ul>
                <p className="lead mt-5">{post.content} </p>
                <br/>
                <p>
                  {post.hashtags.map(tag => <span key={tag.id} className="tag-link" to="/">{tag.content} </span>)}
                </p>
                <hr/>
                <div className="">
                    <div className="mr-1 float-left d-flex">
                        <img src={AvatarImg} alt="book-japanese"/>
                        <p className="ml-3 mt-3">
                            uploaded by {post.userName}
                        </p>
                    </div>
                    <div className="float-right d-flex">
                      <p className="ml-3 mt-3">
                          {post.likesTotal+24} likes &nbsp;
                      </p>
                        {
                          post.isLiked ? (
                        <ul className="brand-icons float-right d-flex">
                          <li onClick={this.likePost}>
                            <button> 
                              <i className="fas fa-thumbs-up fa-lg"></i>
                            </button>
                          </li>
                        </ul>
                            )
                            : (
                        <ul className="brand-icons float-right d-flex">
                          <li onClick={this.likePost}>
                            <button> 
                              <i className="far fa-thumbs-up fa-lg"></i>
                            </button>
                          </li>
                        </ul>
                            )
                        }
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
              
              { this.state.post && post ? (currentUser.isAuthenticated ?(
                ( this.state.post.locked === 0 ? (
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
                ) : ( <h3>Post was locked and new comments are not allowed.</h3> ) )
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
        <Modal show={this.state.showDelete} onHide={this.handleDeleteModalClose}>
            <Modal.Header closeButton>
                <Modal.Title>Are You Sure?</Modal.Title>
            </Modal.Header>
            <Modal.Footer>
                <div className="col-12">
                <Button variant="secondary" className="float-left" onClick={this.handleDeleteModalClose}>
                    Cancel
                </Button>
                <Button variant="danger" className="float-right" onClick={this.deletePost}>
                    Yes, delete
                </Button>
                </div>
            </Modal.Footer>
          </Modal>
      </div>
    )
  }
}
const mapStateToProps = state => ({})

export default connect(mapStateToProps)(PostDetails);