import React, { Component } from 'react';
import axios from 'axios';
import Spinner from '../../assets/images/spinner.gif';
import { Link } from 'react-router-dom';
import { Button, Modal } from 'react-bootstrap';

class KanjiDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            url: '/api/words',
            pagination: [],
            word: {},
            kanjis: {},
            sentences: {},
            articles: {},
            paginateObject: {},
            searchHeading: "",
            searchTotal: "",
            filters: [],
            lists: [],
            show: false,
            wordIsKnown: false
        }

        this.addToList = this.addToList.bind(this);
        this.removeFromList = this.removeFromList.bind(this);
        this.openModal = this.openModal.bind(this);
        this.handleClose = this.handleClose.bind(this);
        this.getUserWordLists = this.getUserWordLists.bind(this);

        // this.addToList = this.addToList.bind(this);
    }

    componentDidMount() {
        let id = this.props.match.params.word_id;
        axios.get('/api/word/' + id)
        .then(res => {
            console.log(res); // word object
            // console.log(res.data.words) // words paginate object
            // console.log(res.data.sentences) // sentences paginate object
            // console.log(res.data.articles) // articles paginate object

            res.data.meaning = res.data.meaning.split("|")
            res.data.meaning = res.data.meaning.join(", ")

            this.setState({
                word: res.data,
                paginateObject: res,
                kanjis: res.data.kanjis,
                articles: res.data.articles,
                sentences: res.data.sentences
            });
        })
        .catch(err => {
            console.log(err);
        });

        if(this.props.currentUser.isAuthenticated){
            this.getUserWordLists();
        }
    };

    componentWillReceiveProps(nextProps) {
        if(nextProps.currentUser.isAuthenticated){
            this.getUserWordLists();
        }
    }

    handleClose(){
        this.setState({show: !this.state.show})
    }

    getUserWordLists(){
        return axios.post(`/api/user/lists/contain`, {
            elementId: this.props.match.params.word_id
        })
        .then(res => {
            let newState = Object.assign({}, this.state);
            newState.lists = res.data.lists.filter(list => {
                if(list.type === 3 && list.elementBelongsToList){
                    newState.wordIsKnown = true;
                    console.log("getUserWordLists. this wordIsKnown " + newState.wordIsKnown);
                }
                if(list.type === 3 || list.type === 7){
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
            // this.setState({show: !this.state.show})
            axios.post("/api/user/list/additemwhileaway", {
                listId: id,
                elementId: this.props.match.params.word_id
            })
            .then(res => {
                let newState = Object.assign({}, this.state);
                newState.lists.find(list => {
                    if(list.id === id){
                        if(list.type === 3 ) {
                            newState.wordIsKnown = true;
                        }
                        return list.elementBelongsToList = true;
                    }
                });
    
                this.setState( newState );
            })
            .catch(err => console.log(err))
      }

      removeFromList(id){
        //   this.setState({show: !this.state.show})
          axios.post("/api/user/list/removeitemwhileaway", {
            listId: id,
            elementId: this.props.match.params.word_id
          })
          .then(res => {
            let newState = Object.assign({}, this.state);
            newState.lists.find(list => {
                if(list.id === id){
                    if(list.type === 3 ) {
                        newState.wordIsKnown = false;
                    }
                    return list.elementBelongsToList = false;
                }
            });

            this.setState( newState );
          })
          .catch(err => console.log(err))
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

        let { word, kanjis, articles } = this.state;

        let singleWord = word ? (
               <div className="row justify-content-center mt-5">
                <div className="col-md-4">
                        <h1>{word.word} <br/>
                        </h1>
                        <p>
                            furigana: {word.furigana}, 
                        </p>
                    </div>
                    <div className="col-md-4">
                        <p>
                            type: {word.word_type}, 
                        </p>
                    </div>
                    <div className="col-md-4">
                        <p>
                            jlpt: {word.jlpt}, <br/> meaning: {word.meaning}
                        </p>
                        <p className="float-right">
                        {this.state.wordIsKnown ? (
                                <i className="fas fa-check-circle text-success"> Learned</i>
                            ): ""}
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

        const kanjiList = kanjis.data ? ( kanjis.data.map(kanji => {
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

        // const sentenceList = sentences.data ? ( sentences.data.map(sentence => {
        //     return (
        //         <div className="row justify-content-center mt-5" key={sentence.id}>
        //             <div className="col-md-12">
        //                 <div className="container">
        //                 <div className="row justify-content-center">
        //                     <div className="col-md-10">
        //                         <h3>{sentence.content}</h3>
        //                     </div>
        //                     <div className="col-md-2">
        //                         {sentence.tatoeba_entry ? 
        //                         ( <a href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`} target="_blank">
        //                             Tatoeba {" "}
        //                             <i className="fas fa-external-link-alt"></i>
        //                         </a> ) : "Local"}
        //                         <Link to={`/api/sentence/${sentence.id}`} className="float-right">
        //                             {/* <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
        //                             details...{" "} 
        //                         </Link>
        //                     </div>
        //                 </div>
        //                 </div>
        //                     <hr/>
        //             </div>
        //             <hr/>
        //         </div>
        //     )
        // })) : "";

        const articleList = articles.data ? ( articles.data.map(article => {
            article.hashtags = article.hashtags.slice(0, 3);
            return (
                <div className="row justify-content-center mt-5" key={article.id}>
                    <div className="col-md-12">
                        <div className="container">
                        <div className="row justify-content-center">
                            <div className="col-md-8">
                                <h3>{article.title_jp}</h3>
                                <p>
                                {article.hashtags.map(tag => <span key={tag.id} className="tag-link" to="/">{tag.content} </span>)}
                                </p>
                            </div>
                            <div className="col-md-2">
                                <p>
                                    Views: {article.viewsTotal + Math.floor(Math.random() * Math.floor(20))} <br/>
                                    Likes: {article.likesTotal + Math.floor(Math.random() * Math.floor(20))} <br/>
                                    Comments: {article.commentsTotal + Math.floor(Math.random() * Math.floor(20))} <br/>
                                </p>
                            </div>
                            <div className="col-md-2">
                                <Link to={`/article/${article.id}`} className="float-right" target="_blank">
                                    {/* <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
                                    details...{" "} 
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
                  <Link to="/words" className="tag-link">Back</Link>
                </span>
                {singleWord}
                <hr/>
                { kanjis.data ? (
                    <h4>kanjis ({kanjis.data.length}) results</h4>
                ) : ""}
                <div className="container">
                {kanjiList}
                </div>
                <hr/>
                {/* { sentences.data ? (
                    <h4>sentences ({sentences.data.length}) results</h4>
                ) : ""} */}
                <div className="container">
                {/* {sentenceList} */} sentences (0) results
                </div>
                <hr/>
                { articles.data ? (
                    <h4>articles ({articles.data.length}) results</h4>
                ) : ""}
                <div className="container">
                {articleList}
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
                    {/* <Button variant="primary" onClick={this.handleClose}>
                        Save Changes
                    </Button> */}
                </Modal.Footer>
                </Modal>
            </div>
        );
    }
}

export default KanjiDetails
