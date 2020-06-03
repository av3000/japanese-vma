import React, { Component } from 'react';
import axios from 'axios';
import Spinner from '../../assets/images/spinner.gif';
import { Link } from 'react-router-dom';

class KanjiDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            url: '/api/kanjis',
            pagination: [],
            kanji: {},
            words: {},
            sentences: {},
            articles: {},
            paginateObject: {},
            searchHeading: "",
            searchTotal: "",
            filters: []
        }

        // this.addToList = this.addToList.bind(this);
    }

    componentDidMount() {
        let id = this.props.match.params.kanji_id;
        axios.get('/api/kanji/' + id)
        .then(res => {
            // console.log(res.data); // kanji object
            // console.log(res.data.words) // words paginate object
            // console.log(res.data.sentences) // sentences paginate object
            // console.log(res.data.articles) // articles paginate object

            res.data.meaning = res.data.meaning.split("|")
            res.data.meaning = res.data.meaning.join(", ")

            res.data.onyomi = res.data.onyomi.split("|")
            res.data.onyomi = res.data.onyomi.join(", ")

            res.data.kunyomi = res.data.kunyomi.split("|")
            res.data.kunyomi = res.data.kunyomi.join(", ")

            this.setState({
                kanji: res.data,
                paginateObject: res,
                words: res.data.words,
                articles: res.data.articles,
                sentences: res.data.sentences
            });
        })
        .catch(err => {
            console.log(err);
        });
    };

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

        let { kanji, words, sentences, articles } = this.state;

        let singleKanji = kanji ? (
               <div className="row justify-content-center mt-5">
                <div className="col-md-4">
                        <h1>{kanji.kanji} <br/>
                        {kanji.hiragana}</h1>
                        <p>
                            meaning: {kanji.meaning}, 
                        </p>
                    </div>
                    <div className="col-md-4">
                        <p>
                            onyomi: {kanji.onyomi}, 
                        </p>
                        <p>
                            kunyomi: {kanji.kunyomi} 
                        </p>
                    </div>
                    <div className="col-md-2">
                        <p>
                            parts : {kanji.radical_parts}
                        </p>
                        <p>
                            strokes: {kanji.stroke_count} 
                        </p>
                    </div>
                    <div className="col-md-2">
                        <p>
                            jlpt: {kanji.jlpt}, 
                        </p>
                        <p>
                            frequency: {kanji.frequency} 
                        </p>
                        <p className="float-right">
                            <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i>
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

        const wordsList = words.data ? ( words.data.map(word => {
            word.meaning = word.meaning.split("|")
            word.meaning = word.meaning.slice(0, 3)
            word.meaning = word.meaning.join(", ")

            return (
                <div className="row justify-content-center mt-5" key={word.id}>
                    <div className="col-md-10">
                        <div className="container">
                        <div className="row justify-content-center">
                            <div className="col-md-6">
                            <h3>{word.word}</h3>
                            </div>
                            <div className="col-md-4">
                            {word.meaning}
                            </div>
                            <div className="col-md-2">
                            <Link to={`/api/word/${word.id}`} className="float-right">
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

        const sentenceList = sentences.data ? ( sentences.data.map(sentence => {

            return (
                <div className="row justify-content-center mt-5" key={sentence.id}>
                    <div className="col-md-12">
                        <div className="container">
                        <div className="row justify-content-center">
                            <div className="col-md-10">
                                <h3>{sentence.content}</h3>
                            </div>
                            <div className="col-md-2">
                                {sentence.tatoeba_entry ? 
                                ( <a href={`https://tatoeba.org/eng/sentences/show/${sentence.tatoeba_entry}`} target="_blank">
                                    Tatoeba {" "}
                                    <i className="fas fa-external-link-alt"></i>
                                </a> ) : "Local"}
                                <Link to={`/api/sentence/${sentence.id}`} className="float-right">
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
               

        return (
            <div className="container">
                 <span className="mt-4">
                  <Link to="/kanjis" className="tag-link">Back</Link>
                </span>
                {singleKanji}
                <hr/>
                { words.data ? (
                    <h4>words ({words.data.length}) results</h4>
                ) : ""}
                <div className="container">
                {wordsList}
                </div>
                <hr/>
                { sentences.data ? (
                    <h4>sentences ({sentences.data.length}) results</h4>
                ) : ""}
                <div className="container">
                {sentenceList}
                </div>
                <hr/>
                { articles.data ? (
                    <h4>articles ({articles.data.length}) results</h4>
                ) : ""}
                <div className="container">
                {articleList}
                </div>
            </div>
        );
    }
}

export default KanjiDetails
