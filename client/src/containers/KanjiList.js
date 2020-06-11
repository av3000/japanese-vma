import React, { Component } from 'react';
import { apiCall } from '../services/api';
import KanjiItem from '../components/kanji/KanjiItem';
import Spinner from '../assets/images/spinner.gif';
import SearchBarKanjis from '../components/search/SearchBarKanjis';


export class KanjiList extends Component {
    constructor(){
        super();
        this.state = {
            url: '/api/kanjis',
            pagination: [],
            kanjis: [],
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
        this.fetchKanjis(this.state.url);
    };

    fetchKanjis(givenUrl){
        return apiCall("get", givenUrl)
            .then(res => {
                let newState = Object.assign({}, this.state);
                newState.paginateObject = res.kanjis;
                newState.kanjis = [...newState.kanjis, ...res.kanjis.data];
                newState.url = res.kanjis.next_page_url;

                newState.searchTotal = "results total: '" + res.kanjis.total + "'";

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
        apiCall("post", "/api/kanjis/search", newState.filters)
        .then(res => {
            if(res.success === true)
            {
                newState.paginateObject = res.kanjis;
                newState.kanjis       = res.kanjis.data ? res.kanjis.data : newState.kanjis;
                newState.url            = res.kanjis.next_page_url;

                newState.searchHeading = "Requested query: '" + newState.filters.keyword +"'";
                newState.searchTotal = "Results total: '" + res.kanjis.total +"'";
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

            newState.paginateObject = res.kanjis;
            newState.kanjis         = [...newState.kanjis, ...res.kanjis.data];
            newState.url            = res.kanjis.next_page_url;

            newState.searchHeading = "Requested query: '" + newState.filters.keyword +"'";
            newState.searchTotal = "Results total: '" + res.kanjis.total +"'";

            return newState;
        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );

            console.log(this.state.kanjis);
        })
        .catch(err => {
            console.log(err);
        })
    }

    loadMore() {
        this.fetchKanjis(this.state.pagination.next_page_url);
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
        let { kanjis } = this.state;
        let kanjiList = kanjis ? ( kanjis.map(k => {

            k.meaning = k.meaning.split("|")
            k.meaning = k.meaning.slice(0, 3)
            k.meaning = k.meaning.join(", ")

            k.onyomi = k.onyomi.split("|")
            k.onyomi = k.onyomi.slice(0, 3)
            k.onyomi = k.onyomi.join(", ")

            k.kunyomi = k.kunyomi.split("|")
            k.kunyomi = k.kunyomi.slice(0, 3)
            k.kunyomi = k.kunyomi.join(", ")

            return (
                <KanjiItem 
                    key={k.id}
                    id={k.id}
                    kanji={k.kanji}
                    stroke_count={k.stroke_count}
                    onyomi={k.onyomi}
                    kunyomi={k.kunyomi}
                    meaning={k.meaning}
                    jlpt={k.jlpt}
                    frequency={k.frequency}
                    parts={k.radical_parts}
                    addToList={this.addToList.bind(this, k.id)}
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
                    <SearchBarKanjis fetchQuery={this.fetchQuery} />
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
                            {kanjiList}
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

export default KanjiList;
