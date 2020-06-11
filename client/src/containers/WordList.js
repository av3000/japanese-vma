import React, { Component } from 'react';
import { apiCall } from '../services/api';
import WordItem from '../components/word/WordItem';
import Spinner from '../assets/images/spinner.gif';
import SearchBarWords from '../components/search/SearchBarWords';


export class WordList extends Component {
    constructor(){
        super();
        this.state = {
            url: '/api/words',
            pagination: [],
            words: [],
            paginateObject: {},
            searchHeading: "",
            searchTotal: "",
            filters: []
        }
        
        this.loadMore = this.loadMore.bind(this);
        this.loadSearchMore = this.loadSearchMore.bind(this);
        this.fetchQuery = this.fetchQuery.bind(this);
        this.fetchMoreQuery = this.fetchMoreQuery.bind(this);
    };

    componentDidMount() {
        this.fetchWords(this.state.url);
    };

    fetchWords(givenUrl){
        return apiCall("get", givenUrl)
            .then(res => {
                console.log(res);
                let newState = Object.assign({}, this.state);
                newState.paginateObject = res.words;
                newState.words = [...newState.words, ...res.words.data];
                newState.url = res.words.next_page_url;

                newState.searchTotal = "results total: '" + res.words.total + "'";

                return newState;
            })
            .then(newState => {
                newState.pagination = this.makePagination(newState.paginateObject);
                this.setState( newState );
            })
            .catch(err => {
                console.log(err);
            })
    };

    fetchQuery(queryParams) {
        let newState = Object.assign({}, this.state);
        newState.filters = queryParams;
        apiCall("post", "/api/words/search", newState.filters)
        .then(res => {
            if(res.success === true)
            {
                newState.paginateObject = res.words;
                newState.words       = res.words.data ? res.words.data : newState.words;
                newState.url            = res.words.next_page_url;

                newState.searchHeading = "Requested query: '" + newState.filters.keyword +"'";
                newState.searchTotal = "Results total: '" + res.words.total +"'";
                return newState;
            }

        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );
        })
        .catch(err => {
            newState.searchHeading = "No results for tag: " + newState.filters.keyword;
            this.setState( newState );
            console.log(err);
        })
    }

    fetchMoreQuery(givenUrl) {
        let newState = Object.assign({}, this.state);
        apiCall("post", givenUrl, newState.filters)
        .then(res => {
            console.log(res);

            newState.paginateObject = res.words;
            newState.words         = [...newState.words, ...res.words.data];
            newState.url            = res.words.next_page_url;

            newState.searchHeading = "Requested query: '" + newState.filters.keyword +"'";
            newState.searchTotal = "Results total: '" + res.words.total +"'";

            return newState;
        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );

            console.log(this.state.words);
        })
        .catch(err => {
            console.log(err);
        })
    }

    loadMore() {
        this.fetchWords(this.state.pagination.next_page_url);
    }

    loadSearchMore(){
        this.fetchMoreQuery(this.state.pagination.next_page_url);
    }

    makePagination(data) {
        let pagination = {
            current_page: data.current_page,
            last_page: data.last_page,
            next_page_url: data.next_page_url,
            prev_page_url: data.prev_page_url,
        };

        return pagination;        
    };

    addToList(id){
        console.log("add to list: " + id);
    }


    render() {
        let { words } = this.state;
        let wordList = words ? ( words.map(w => {

            // w.meaning = w.meaning.split("|")
            // w.meaning = w.meaning.slice(0, 3)
            // w.meaning = w.meaning.join(", ")

            return (
                <WordItem 
                    key={w.id}
                    id={w.id}
                    word={w.word}
                    furigana={w.furigana}
                    word_type={w.word_type}
                    meaning={w.meaning}
                    jlpt={w.jlpt}
                    addToList={this.addToList.bind(this, w.id)}
                />
            );
                
        }) ): (
        <div className="container mt-5">
            <div className="row justify-content-center">
                <img src={Spinner}/>
            </div>
        </div>
        );

        return (
            <div className="container mt-5">
                <div className="row justify-content-center">
                    <SearchBarWords fetchQuery={this.fetchQuery} />
                    {/* by tag */}
                    {/* by keyword keyword */}
                    {/* by newest/popular */}
                </div>
                <div className="container mt-5">
                    <div className="row justify-content-center">
                    {this.state.searchHeading ? (
                        <h4>
                            {this.state.searchHeading}
                        </h4> 
                        )
                        : ""
                    }  
                    &nbsp;
                    {this.state.searchTotal ? (
                        <h4>
                            {this.state.searchTotal}
                        </h4> 
                        )
                        : ""
                    }
                    </div>
                    <div className="row">
                        <div className="col-lg-8 col-md-10 mx-auto">
                            {wordList}
                        </div>
                    </div>
                </div>
                <div className="row justify-content-center">
                { this.state.pagination.last_page === this.state.pagination.current_page ? 
                    "no more results..." 
                        : 
                    this.state.url.includes("search") ? 
                        (<button className="btn btn-outline-primary brand-button col-6" onClick={this.loadSearchMore}>Load More</button>) 
                        :
                        (<button className="btn btn-outline-primary brand-button col-6" onClick={this.loadMore}>Load More</button>)
                
                }
                </div>
            </div>
        )
    }
}

export default WordList;
