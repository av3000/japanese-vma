import React, { Component } from 'react'
import axios from 'axios'
import { Link, Redirect } from 'react-router-dom';
import { connect } from "react-redux";
import { apiCall } from '../../services/api';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import AvatarImg from '../../assets/images/avatar-woman.svg';
import Spinner from '../../assets/images/spinner.gif';
import { hideLoader, showLoader } from "../../store/actions/application";
import CommentList from '../comment/CommentList';
import CommentForm from '../comment/CommentForm';
import { Button, Modal } from 'react-bootstrap';

class ArticleDetails extends Component {
   _isMounted = false;

    constructor(props) {
        super(props);
        this.state = {
            article: null,
            lists: [],
            showBookmark: false,
            showPdf: false,
            showDelete: false,
            showStatus: false,
            tempStatus: false
        };

        this.likeArticle = this.likeArticle.bind(this);
        this.addComment = this.addComment.bind(this);
        this.deleteComment = this.deleteComment.bind(this);
        this.likeComment = this.likeComment.bind(this);
        this.editComment = this.editComment.bind(this);
        this.deleteArticle = this.deleteArticle.bind(this);
        this.downloadKanjisPdf = this.downloadKanjisPdf.bind(this);
        this.downloadWordsPdf = this.downloadWordsPdf.bind(this);
        this.toggleStatus = this.toggleStatus.bind(this);
        this.handleStatusChange = this.handleStatusChange.bind(this);

        this.openBookmarkModal = this.openBookmarkModal.bind(this);
        this.openPdfModal = this.openPdfModal.bind(this);
        this.openDeleteModal = this.openDeleteModal.bind(this);
        this.openStatusModal = this.openStatusModal.bind(this);
        
        this.handleBookmarkClose = this.handleBookmarkClose.bind(this);
        this.handlePdfClose = this.handlePdfClose.bind(this);
        this.handleDeleteModalClose = this.handleDeleteModalClose.bind(this);
        this.handleStatusModalClose = this.handleStatusModalClose.bind(this);

        this.getUserArticleLists = this.getUserArticleLists.bind(this);
    };

  componentWillUnmount() {
    this._isMounted = false;
  }

  componentDidMount(){
    this._isMounted = true;
    if (this._isMounted) {
        let id = this.props.match.params.article_id;
        this.getArticleWithAuth(id);
    }
  };

  getArticleWithAuth(id){
    axios.get('/api/article/' + id)
      .then(res => {

          let newState = Object.assign({}, this.state);
          newState.article = res.data.article;
          newState.tempStatus = res.data.article.status;

          this.setState(newState);

          return newState;
      })
      .then(newState => {
        if(!newState.article)
        {
            this.props.history.push('/articles');
        }
        else if(this.props.currentUser.isAuthenticated)
        {
          return apiCall("post", `/api/article/${id}/checklike`)
            .then(res => { 
              newState.article.isLiked = res.isLiked;
              this.setState(newState);
            });
        }
      })
      .then( res => {
          let newState = Object.assign({}, this.state);
          if(this.props.currentUser.isAuthenticated)
          {
            newState.article.comments.map(comment => {
                let temp = comment.likes.find(like => like.user_id === this.props.currentUser.user.id)
                if(temp) { comment.isLiked = true}
                else { comment.isLiked = false}
            })

           this.setState(newState);
          }
      })
      .then(res => {
        if(this.props.currentUser.isAuthenticated)
          {
            this.getUserArticleLists();
          }
      })
      .catch(err => {
        this.props.history.push('/articles');
      });

  }

  // Actions

  getUserArticleLists(){
    return axios.post(`/api/user/lists/contain`, {
        elementId: this.props.match.params.article_id
    })
    .then(res => {
        let newState = Object.assign({}, this.state);
        newState.lists = res.data.lists.filter(list => {
            if(list.type === 9){
                return list;
            }
        })

        this.setState( newState );
    })
    .catch(err => {
        console.log(err);
    })
  }

  deleteArticle(){
    return apiCall("delete", `/api/article/${this.state.article.id}`)
      .then(res => { 
        this.props.history.push('/articles');
      })
      .catch(err => {
        console.log(err);
      });
  }

  addToList(id){
      this.setState({show: !this.state.show})
      axios.post("/api/user/list/additemwhileaway", {
          listId: id,
          elementId: this.props.match.params.article_id
      })
      .then(res => {
        let newState = Object.assign({}, this.state);
        newState.lists.find(list => {
            if(list.id === id){
                list.elementBelongsToList = true;
            }
        });

        this.setState( newState );
       })
      .catch(err => console.log(err))
  }

  removeFromList(id){
      this.setState({show: !this.state.show})
      axios.post("/api/user/list/removeitemwhileaway", {
        listId: id,
        elementId: this.props.match.params.article_id
      })
      .then(res => {
        let newState = Object.assign({}, this.state);
        newState.lists.find(list => {
            if(list.id === id){
                list.elementBelongsToList = false;
            }
        });

        this.setState( newState );
       })
      .catch(err => console.log(err))
  }

  likeArticle() {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }
    else {

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
    }
  };

  downloadKanjisPdf() {
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

  downloadWordsPdf() {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }
    else {
      this.props.dispatch( showLoader("Creating a PDF, please wait.") );

      let id = this.state.article.id;
      axios.get('/api/article/'+id+'/words-pdf', {
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

  toggleStatus(){
    this.handleStatusModalClose();
    return apiCall("post", `/api/article/${this.state.article.id}/setstatus`, {
      status: this.state.tempStatus
    })
      .then( res => {
        let newState = Object.assign({}, this.state);
        newState.article.status = res.newStatus;
        newState.tempStatus = res.newStatus;
        this.setState( newState );
      })
      .catch( err => {
        console.log(err);
      })
  }

  handleStatusChange(e){
    let tempStatus = parseInt(e.target.value);
    let newState = Object.assign({}, this.state);
    newState.tempStatus = tempStatus;
    this.setState( newState );
  }

  // Modals

  openStatusModal() {
    if(this.props.currentUser.isAuthenticated === false){
      this.props.history.push('/login');
    }
    else {
        this.setState({showStatus: !this.state.showStatus})
        //   next decision to pick which list to add.
    }
  }

  handleStatusModalClose(){
    this.setState({showStatus: !this.state.showStatus})
  }

  openBookmarkModal(){
    if(this.props.currentUser.isAuthenticated === false){
        this.props.history.push('/login');
    }
    else {
        this.setState({showBookmark: !this.state.showBookmark})
        //   next decision to pick which list to add.
    }
}

  openPdfModal() {
    if(this.props.currentUser.isAuthenticated === false){
      this.props.history.push('/login');
    }
    else {
        this.setState({showPdf: !this.state.showPdf})
        //   next decision to pick which pdf to download.
    }
  }

  handleDeleteModalClose(){
    this.setState({showDelete: !this.state.showDelete})
  }

  openDeleteModal() {
    if(this.props.currentUser.isAuthenticated === false){
      this.props.history.push('/login');
    }
    else {
        this.setState({showDelete: !this.state.showDelete})
    }
  }

  handleBookmarkClose(){
    this.setState({showBookmark: !this.state.showBookmark})
  }

  handlePdfClose(){
    this.setState({showPdf: !this.state.showPdf})
  }

  // Comments

  likeComment(commentId) {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }
    else {
      let theComment = this.state.article.comments.find(comment => comment.id === commentId)

      let endpoint = theComment.isLiked === true ? "unlike" : "like";

      axios.post('/api/article/'+this.state.article.id+'/comment/'+commentId+'/'+endpoint)
        .then(res => {
          let newState = Object.assign({}, this.state);
          let index = this.state.article.comments.findIndex(comment => comment.id === commentId)
          newState.article.comments[index].isLiked = !newState.article.comments[index].isLiked
          
          if(endpoint === "unlike"){
              newState.article.comments[index].likesTotal -= 1;
          }
          else if (endpoint === "like"){
            newState.article.comments[index].likesTotal += 1;
          }

          this.setState(newState);
        })
        .catch(err => {
            console.log(err);
        })
    }

  }

  addComment(comment) {
      let newState = Object.assign({}, this.state);
      newState.article.comments.unshift(comment)
      this.setState( newState );
  }

  deleteComment(commentId) {
    return apiCall("delete", `/api/article/${this.state.article.id}/comment/${commentId}`)
      .then(res => { 
        let newState = Object.assign({}, this.state);
        newState.article.comments = newState.article.comments.filter(comment => comment.id !== commentId);
        this.setState( newState );
      })
      .catch(err => {
        console.log(err);
      });

  }

  editComment(commentId){
    console.log(commentId);
  }

  render() {
    
    const { article     } = this.state;
    const { currentUser } = this.props;
    let comments = article ? article.comments : "";

    let articleStatus    = "";
    let articlePublicity = "";

    if(this.state.article){
      if     (this.state.article.status === 0) { articleStatus = "pending";   }
      else if(this.state.article.status === 1) { articleStatus = "reviewing"; }
      else if(this.state.article.status === 2) { articleStatus = "rejected";  }
      else if(this.state.article.status === 3) { articleStatus = "approved";  }

      if(this.state.article.publicity === 1) { articlePublicity = "public | ";  }
      else                                   { articlePublicity = "private | "; }
    }

    const singleArticle = article ? (
      <div className="container">
          <div className="row justify-content-center">
              <div className="col-lg-8 ">
                <span className="row mt-4">
                <Link to="/articles" className="tag-link"> <i className="fas fa-arrow-left"></i> Back</Link>
                </span>
                <h1 className="mt-4">{article.title_jp}</h1>
                <p className="text-muted"> 
                    Posted on {article.jp_year} {article.jp_month} {article.jp_day} {article.jp_hour}
                    <br/><span>{article.viewsTotal + 40} views</span> <br/>
                    {currentUser.user.id === article.user_id || currentUser.user.isAdmin ? articlePublicity : ""}
                    {currentUser.user.id === article.user_id || currentUser.user.isAdmin ? articleStatus : ""}
                </p>
                {/* BEGIN Action icons */}
                <ul className="brand-icons mr-1 float-right d-flex">
                    { currentUser.user.isAdmin ? 
                    (
                      <li onClick={this.openStatusModal}>
                        <button>
                          Review
                        </button>
                      </li>
                    ) : ""
                    }
                    {currentUser.user.id === article.user_id ?
                      (
                      <li onClick={this.openDeleteModal}>
                        <button> 
                          <i className="far fa-trash-alt fa-lg"></i>
                        </button>
                      </li>) : ""
                    }
                    {currentUser.user.id === article.user_id ?
                      (<Link to={`/article/edit/${article.id}`}> 
                      <li>
                        <button> 
                          <i className="fas fa-pen-alt fa-lg"></i>
                        </button>
                      </li>
                      </Link>) : ("")
                    }
                </ul>
                {/* END action icons */}
                <img className="img-fluid rounded mb-3" src={DefaultArticleImg} alt="default-article-img"/>
                <p className="lead">{article.content_jp} </p>
                <br/>
                <p>
                  {article.hashtags.map(tag => <span key={tag.id} className="tag-link" to="/">{tag.content} </span>)}
                    <br/> <a href={article.source_link} target="_blank">original source</a>
                </p>
                <hr/>
                <div className="">
                    <div className="mr-1 float-left d-flex">
                        <img src={AvatarImg} alt="book-japanese"/>
                        <p className="ml-3 mt-3">
                            created by {article.userName}
                        </p>
                    </div>
                    <div className="float-right d-flex">
                      <p className="ml-3 mt-3">
                          {article.likesTotal+24} likes &nbsp;
                      </p>
                      <ul className="brand-icons float-right d-flex">
                        {article.isLiked ? (
                            <li onClick={this.likeArticle}>
                              <button> 
                                <i className="fas fa-thumbs-up fa-lg"></i>
                              </button>
                            </li>
                            )
                            : (
                            <li onClick={this.likeArticle}>
                              <button> 
                                <i className="far fa-thumbs-up fa-lg"></i>
                              </button>
                            </li>
                            )
                        }
                        <li onClick={this.openBookmarkModal}>
                          <button>
                            <i className="far fa-bookmark fa-lg"></i>
                          </button>
                        </li>
                        <li onClick={this.openPdfModal}>
                          <button>
                            <i className="fas fa-file-download fa-lg"></i>
                          </button>
                        </li>
                    </ul>
                    </div>
                </div>


              </div>
          </div>
      </div>
    ) : "";

    // Model for Bookmark adding to lists
    let addModal = this.state.lists ? (this.state.lists.map(list => {
      return (
          <div key={list.id}>
              <div className="col-9"> <Link to={`/list/${list.id}`}>{list.title}</Link>
                  {list.elementBelongsToList ? 
                  (<button className="btn btn-sm btn-danger" onClick={this.removeFromList.bind(this, list.id)}>-</button>)
                  :
                  (<button className="btn btn-sm btn-light" onClick={this.addToList.bind(this, list.id)}>+</button>)
              }
              </div>
          </div>
          
      ) })) : ("");
      
    return (
      <div className="container">
        {this.state.article ? singleArticle : (
          <div className="container">
              <div className="row justify-content-center">
                  <img src={Spinner} alt="spinner"/>
              </div>
          </div>
        )}
        <br/>
        <div className="row justify-content-center">
              { article ? (currentUser.isAuthenticated ?(
                <div className="col-lg-8">
                <hr/>
                  <h6>Share what's on your mind</h6>
                  <CommentForm 
                    addComment={this.addComment}
                    currentUser={currentUser} 
                    objectId={this.state.article.id}
                    objectType="article"
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
                    objectId={this.state.article.id}
                    currentUser={currentUser}
                    comments={comments}
                    deleteComment={this.deleteComment}
                    likeComment={this.likeComment}
                    editComment={this.editComment}
                    />) : (
                      <div className="container">
                        <div className="row justify-content-center">
                            <img src={Spinner} alt="spinner"/>
                        </div>
                    </div>
                    )}
              </div>
        </div>

          {this.state.lists ? (
            <Modal show={this.state.showBookmark} onHide={this.handleBookmarkClose}>
            <Modal.Header closeButton>
                <Modal.Title>Choose List to add</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                {addModal}
                <small> <Link to="/newlist">Want new list?</Link> </small>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={this.handleBookmarkClose}>
                    Close
                </Button>
            </Modal.Footer>
          </Modal>
          ): ""}

          <Modal show={this.state.showPdf} onHide={this.handlePdfClose}>
            <Modal.Header closeButton>
                <Modal.Title>Choose which data you want to download.</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <button className="btn btn-outline brand-button" onClick={this.downloadKanjisPdf}> Kanji PDF </button>
                {/* <button className="btn btn-outline brand-button" onClick={this.downloadWordsPdf}> Words PDF </button> */}
            </Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={this.handlePdfClose}>
                    Close
                </Button>
            </Modal.Footer>
          </Modal>

          <Modal show={this.state.showDelete} onHide={this.handleDeleteModalClose}>
            <Modal.Header closeButton>
                <Modal.Title>Are You Sure?</Modal.Title>
            </Modal.Header>
            <Modal.Footer>
                <div className="col-12">
                <Button variant="secondary" className="float-left" onClick={this.handleDeleteModalClose}>
                    Cancel
                </Button>
                <Button variant="danger" className="float-right" onClick={this.deleteArticle}>
                    Yes, delete
                </Button>
                </div>
            </Modal.Footer>
          </Modal>

          {article ? (
            <Modal show={this.state.showStatus} onHide={this.handleStatusModalClose}>
            <Modal.Header closeButton>
                <Modal.Title>Are You Sure?</Modal.Title>
            </Modal.Header>
            <Modal.Footer>
                <div className="col-12">
                <select name="tempStatus" value={this.state.tempStatus} className="form-control form-control-sm w-75 mb-2" onChange={this.handleStatusChange}>
                    <option value="0">Pending</option>
                    <option value="1">Review</option>
                    <option value="2">Reject</option>
                    <option value="3">Approve</option>
                </select> 
                <Button variant="secondary" className="float-left" onClick={this.handleStatusModalClose}>
                    Cancel
                </Button>
                <Button variant="success" className="float-right" onClick={this.toggleStatus}>
                    Submit
                </Button>
                </div>
            </Modal.Footer>
          </Modal>
          ) : ""}
      </div>
    )
  }
}
const mapStateToProps = state => ({})

export default connect(mapStateToProps)(ArticleDetails);