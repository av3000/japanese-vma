import React, { Component } from 'react';
import axios from 'axios';
import { apiCall } from '../../services/api';
import Spinner from '../../assets/images/spinner.gif';
import { Link } from 'react-router-dom';
import CommentList from '../comment/CommentList';
import CommentForm from '../comment/CommentForm';
import { Button, Modal } from 'react-bootstrap';

class SentenceDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            // url: '/api/words', resource url which will be paginated
            pagination: [],
            sentence: {},
            kanjis: [],
            // words: {},
            paginateObject: {},
            searchHeading: "",
            searchTotal: "",
            filters: [],
            lists: [],
            show: false
        }

        this.addToList = this.addToList.bind(this);
        this.removeFromList = this.removeFromList.bind(this);
        this.openModal = this.openModal.bind(this);
        this.handleClose = this.handleClose.bind(this);
        this.getUserSentenceLists = this.getUserSentenceLists.bind(this);

        this.addComment = this.addComment.bind(this);
        this.deleteComment = this.deleteComment.bind(this);
        this.likeComment = this.likeComment.bind(this);
        this.editComment = this.editComment.bind(this);
    }

    componentDidMount() {
        let id = this.props.match.params.sentence_id;
        axios.get('/api/sentence/' + id)
        .then(res => {
            console.log(res); // sentence object

            this.setState({
                sentence: res.data,
                paginateObject: res,
                kanjis: res.data.kanjis
                // words: res.data.words,
            });
        })
        .then( res => {
            let newState = Object.assign({}, this.state);
            if(this.props.currentUser.isAuthenticated)
            {
              newState.sentence.comments.map(comment => {
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

        this.getUserSentenceLists();
    };

    handleClose(){
        this.setState({show: !this.state.show})
    }

    getUserSentenceLists(){
        return axios.post(`/api/user/lists/contain`, {
            elementId: this.props.match.params.sentence_id
        })
        .then(res => {
            let newState = Object.assign({}, this.state);
            newState.lists = res.data.lists.filter(list => {
                if(list.type === 4 || list.type === 8){
                    return list;
                }
            })

            this.setState( newState );
        })
        .catch(err => {
            console.log(err);
        })
    }

    openModal(){
        if(this.props.currentUser.isAuthenticated === false){
            this.props.history.push('/login');
        }
        else {
            this.setState({show: !this.state.show})
            //   next decision to pick which list to add.
        }
    }

    addToList(id){
        console.log("addto ")
        console.log(id);
        this.setState({show: !this.state.show})
        axios.post("/api/user/list/additemwhileaway", {
            listId: id,
            elementId: this.props.match.params.sentence_id
        })
        .then(res => console.log(res))
        .catch(err => console.log(err))
        window.location.reload(false);
    }

    removeFromList(id){
        console.log("removefrom")
        console.log(id);
        this.setState({show: !this.state.show})
        axios.post("/api/user/list/removeitemwhileaway", {
        listId: id,
        elementId: this.props.match.params.sentence_id
        })
        .then(res => console.log(res))
        .catch(err => console.log(err))
        window.location.reload(false);
    }

    likeComment(commentId) {
    if(!this.props.currentUser.isAuthenticated)
    {
        this.props.history.push('/login');
    }

    let theComment = this.state.sentence.comments.find(comment => comment.id === commentId)

    let endpoint = theComment.isLiked === true ? "unlike" : "like";

    axios.post('/api/sentence/'+this.state.sentence.id+'/comment/'+commentId+'/'+endpoint)
        .then(res => {
        let newState = Object.assign({}, this.state);
        let index = this.state.sentence.comments.findIndex(comment => comment.id === commentId)
        newState.sentence.comments[index].isLiked = !newState.sentence.comments[index].isLiked
        
        if(endpoint === "unlike"){
            newState.sentence.comments[index].likesTotal -= 1;
        }
        else if (endpoint === "like"){
            newState.sentence.comments[index].likesTotal += 1;
        }

        this.setState(newState);
        })
        .catch(err => {
            console.log(err);
        })

    }

    addComment(comment) {
        let newState = Object.assign({}, this.state);
        newState.sentence.comments.unshift(comment)
        this.setState( newState );
    }

    deleteComment(commentId) {
    return apiCall("delete", `/api/sentence/${this.state.sentence.id}/comment/${commentId}`)
        .then(res => { 
        // console.log(res);
        let newState = Object.assign({}, this.state);
        newState.sentence.comments = newState.sentence.comments.filter(comment => comment.id !== commentId);
        this.setState( newState );
        // window.location.reload(false);
        })
        .catch(err => {
        console.log(err);
        });

    }

    editComment(commentId){
    console.log("editComment");
    console.log(commentId);
    }
    

    // fetchQuery(queryParams) {
    //     let newState = Object.assign({}, this.state);
    //     newState.filters = queryParams;
    //     apiCall("post", "/api/lists/search", newState.filters)
    //     .then(res => {
    //         if(res.success === true)
    //         {
    //             newState.paginateObject = res.lists;
    //             newState.lists       = res.lists.data ? res.lists.data : newState.lists;
    //             newState.url            = res.lists.next_page_url;

    //             newState.searchHeading = "Requested query: " + newState.filters.title;
    //             newState.searchTotal = "results total: " + res.lists.total;
    //             return newState;
    //         }

    //     })
    //     .then(newState => {
    //         newState.pagination = this.makePagination(newState.paginateObject);

    //         this.setState( newState );
    //     })
    //     .catch(err => {
    //         newState.searchHeading = "No results for tag: " + newState.filters.title;
    //         this.setState( newState );
    //         console.log(err);
    //     })
    // }

    // fetchLists(givenUrl){
    //     return apiCall("get", givenUrl)
    //         .then(res => {
    //             let newState = Object.assign({}, this.state);
    //             newState.paginateObject = res.lists;
    //             newState.lists = [...newState.lists, ...res.lists.data];
    //             newState.url = res.lists.next_page_url;

    //             newState.searchTotal = "results total: " + res.lists.total;

    //             return newState;
    //         })
    //         .then(newState => {
    //             newState.pagination = this.makePagination(newState.paginateObject);
    //             this.setState( newState );
    //             console.log(this.state);
    //         })
    //         .catch(err => {
    //             console.log(err);
    //         })
    // };

    // fetchMoreQuery(givenUrl) {
    //     let newState = Object.assign({}, this.state);
    //     apiCall("post", givenUrl, newState.filters)
    //     .then(res => {
    //         console.log(res);

    //         newState.paginateObject = res.lists;
    //         newState.lists       = [...newState.lists, ...res.lists.data];
    //         newState.url            = res.lists.next_page_url;

    //         newState.searchHeading = "Requested query: " + newState.filters.title;
    //         newState.searchTotal = "results total: " + res.lists.total;

    //         return newState;
    //     })
    //     .then(newState => {
    //         newState.pagination = this.makePagination(newState.paginateObject);

    //         this.setState( newState );

    //         console.log(this.state.lists);
    //     })
    //     .catch(err => {
    //         console.log(err);
    //     })
    // }

    // loadMore() {
    //     this.fetchLists(this.state.pagination.next_page_url);
    // }

    // loadSearchMore(){
    //     this.fetchMoreQuery(this.state.pagination.next_page_url);
    // }

    // makePagination(data) {
    //     let pagination = {
    //         current_page: data.current_page,
    //         last_page: data.last_page,
    //         next_page_url: data.next_page_url,
    //         prev_page_url: data.prev_page_url,
    //     };

    //     return pagination;        
    // };

    render() {

        const { currentUser } = this.props;
        let { kanjis, sentence } = this.state;
        let comments = sentence ? sentence.comments : "";

        let singleSentence = sentence ? (
               <div className="row justify-content-center mt-5">
                <div className="col-md-8">
                        <h4>
                            {sentence.content} 
                        </h4>
                        {sentence.user_id ? (
                            <p>
                                UserAuthor - {sentence.user_id}
                            </p>
                        ) : 
                            <p>
                                Tatoeba link: <a href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`}>{sentence.tatoeba_entry}</a>
                            </p>
                        }
                    </div>
                    <div className="col-md-4">
                        <p className="float-right">
                            <i onClick={this.openModal} className="far fa-bookmark ml-3 fa-lg mr-2"></i>
                            {/* <i className="fas fa-external-link-alt fa-lg"></i> */}
                        </p>
                    </div>
               </div>
        ) : (
            <div className="container mt-5">
                <div className="row justify-content-center">
                    <img src={Spinner}/>
                </div>
            </div>
        );

        const kanjiList = kanjis ? ( kanjis.map(kanji => {
            kanji.meaning = kanji.meaning.split("|")
            kanji.meaning = kanji.meaning.slice(0, 3)
            kanji.meaning = kanji.meaning.join(", ")

            return (
                <div className="row justify-content-center mt-5" key={kanji.id}>
                    <div className="col-md-10">
                        <div className="container">
                        <div className="row justify-content-center">
                            <div className="col-md-6">
                            <h3>{kanji.kanji}</h3>
                            </div>
                            <div className="col-md-4">
                            {kanji.meaning}
                            </div>
                            <div className="col-md-2">
                            <Link to={`/api/kanji/${kanji.id}`} className="float-right">
                                {/* <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
                                <i className="fas fa-external-link-alt fa-lg"></i>
                            </Link>
                            </div>
                        </div>
                        </div>
                            <hr/>
                    </div>
                    <hr/>
                </div>
            )
        })) : "";

        // const wordsList = words.data ? ( words.data.map(word => {
        //     word.meaning = word.meaning.split("|")
        //     word.meaning = word.meaning.slice(0, 3)
        //     word.meaning = word.meaning.join(", ")

        //     return (
        //         <div className="row justify-content-center mt-5" key={word.id}>
        //             <div className="col-md-10">
        //                 <div className="container">
        //                 <div className="row justify-content-center">
        //                     <div className="col-md-6">
        //                     <h3>{word.word}</h3>
        //                     </div>
        //                     <div className="col-md-4">
        //                     {word.meaning}
        //                     </div>
        //                     <div className="col-md-2">
        //                     <Link to={`/word/${word.id}`} className="float-right">
        //                         {/* <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
        //                         <i className="fas fa-external-link-alt fa-lg"></i>
        //                     </Link>
        //                     </div>
        //                 </div>
        //                 </div>
        //                     <hr/>
        //             </div>
        //             <hr/>
        //         </div>
        //     )
        // })) : "";

        // Model for radical addint to lists
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
                 <span className="mt-4">
                  <Link to="/sentences" className="tag-link">Back</Link>
                </span>
                {singleSentence}
                <hr/>
                { kanjis.data ? (
                    <h4>kanjis ({kanjis.data.length}) results</h4>
                ) : ""}
                <div className="container">
                {kanjiList}
                </div>
                <hr/>
                <br/>
                <div className="row justify-content-center">
              { sentence ? (currentUser.isAuthenticated ?(
                        <div className="col-lg-8">
                        <hr/>
                        <h6>Share what's on your mind</h6>
                        <CommentForm 
                            addComment={this.addComment}
                            currentUser={currentUser} 
                            objectId={this.state.sentence.id}
                            objectType="sentence"
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
                            objectId={this.state.sentence.id}
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
                <Modal show={this.state.show} onHide={this.handleClose}>
                <Modal.Header closeButton>
                    <Modal.Title>Choose List to add</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {addModal}
                    <small> <Link to="/newlist">Want new list?</Link> </small>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={this.handleClose}>
                        Close
                    </Button>
                </Modal.Footer>
                </Modal>
            </div>
        );
    }
}

export default SentenceDetails
