import React, { Component } from 'react';
import { apiCall } from '../services/api';
import SentenceItem from '../components/sentence/SentenceItem';
import Spinner from '../assets/images/spinner.gif';
import SearchBar from '../components/search/Searchbar';


export class SentenceList extends Component {
    constructor(){
        super();
        this.state = {
            url: '/api/sentences',
            pagination: [],
            sentences: [],
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
        this.fetchSentences(this.state.url);
    };

    fetchSentences(givenUrl){
        return apiCall("get", givenUrl)
            .then(res => {
                console.log(res);
                let newState = Object.assign({}, this.state);
                newState.paginateObject = res.sentences;
                newState.sentences = [...newState.sentences, ...res.sentences.data];
                newState.url = res.sentences.next_page_url;

                newState.searchTotal = "results total: '" + res.sentences.total + "'";

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
        apiCall("post", "/api/sentences/search", newState.filters)
        .then(res => {
            if(res.success === true)
            {
                newState.paginateObject  = res.sentences;
                newState.sentences       = res.sentences.data ? res.sentences.data : newState.sentences;
                newState.url             = res.sentences.next_page_url;

                newState.searchHeading = "Requested query: '" + newState.filters.title +"'";
                newState.searchTotal = "Results total: '" + res.sentences.total +"'";
                return newState;
            }

        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );
        })
        .catch(err => {
            newState.searchHeading = "No results for tag: " + newState.filters.title;
            this.setState( newState );
            console.log(err);
        })
    }

    fetchMoreQuery(givenUrl) {
        let newState = Object.assign({}, this.state);
        apiCall("post", givenUrl, newState.filters)
        .then(res => {
            console.log(res);

            newState.paginateObject = res.sentences;
            newState.sentences         = [...newState.sentences, ...res.sentences.data];
            newState.url            = res.sentences.next_page_url;

            newState.searchHeading = "Requested query: '" + newState.filters.title +"'";
            newState.searchTotal = "Results total: '" + res.sentences.total +"'";

            return newState;
        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );

            console.log(this.state.sentences);
        })
        .catch(err => {
            console.log(err);
        })
    }

    loadMore() {
        this.fetchSentences(this.state.pagination.next_page_url);
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
        let { sentences } = this.state;
        let sentenceList = sentences ? ( sentences.map(s => {
            return (
                <SentenceItem 
                    key={s.id}
                    id={s.id}
                    tatoeba_entry={s.tatoeba_entry}
                    userId={s.user_id}
                    sentence={s.content}
                    addToList={this.addToList.bind(this, s.id)}
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
                    <SearchBar fetchQuery={this.fetchQuery} />
                    {/* by tag */}
                    {/* by title keyword */}
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
                            {sentenceList}
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

export default SentenceList;
