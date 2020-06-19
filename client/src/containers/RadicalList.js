import React, { Component } from 'react';
import { apiCall } from '../services/api';
import RadicalItem from '../components/radical/RadicalItem';
import Spinner from '../assets/images/spinner.gif';
import SearchBarRadicals from '../components/search/SearchBarRadicals';


export class RadicalList extends Component {
    constructor(){
        super();
        this.state = {
            url: '/api/radicals',
            pagination: [],
            radicals: [],
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
        this.fetchRadicals(this.state.url);
    };

    fetchRadicals(givenUrl){
        return apiCall("get", givenUrl)
            .then(res => {
                let newState = Object.assign({}, this.state);
                newState.paginateObject = res.radicals;
                newState.radicals = [...newState.radicals, ...res.radicals.data];
                newState.url = res.radicals.next_page_url;

                newState.searchTotal = "results total: '" + res.radicals.total + "'";

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
        apiCall("post", "/api/radicals/search", newState.filters)
        .then(res => {
            if(res.success === true)
            {
                newState.paginateObject = res.radicals;
                newState.radicals       = res.radicals.data ? res.radicals.data : newState.radicals;
                newState.url            = res.radicals.next_page_url;

                newState.searchHeading = res.requestedQuery;
                newState.searchTotal = "Results total: '" + res.radicals.total +"'";
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

            newState.paginateObject = res.radicals;
            newState.radicals       = [...newState.radicals, ...res.radicals.data];
            newState.url            = res.radicals.next_page_url;

            newState.searchTotal = "Results total: '" + res.radicals.total +"'";

            return newState;
        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );

            console.log(this.state.radicals);
        })
        .catch(err => {
            console.log(err);
        })
    }

    loadMore() {
        this.fetchRadicals(this.state.pagination.next_page_url);
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
        let { radicals } = this.state;
        let radicalList = radicals ? ( radicals.map(r => (
                <RadicalItem 
                    key={r.id}
                    id={r.id}
                    radical={r.radical}
                    strokes={r.strokes}
                    meaning={r.meaning}
                    hiragana={r.hiragana}
                    addToList={this.addToList.bind(this, r.id)}
                />
        )) ): (
        <div className="container mt-5">
            <div className="row justify-content-center">
                <img src={Spinner} alt="spinner"/>
            </div>
        </div>
        );

        return (
            <div className="container mt-5">
                <div className="row justify-content-center">
                    <SearchBarRadicals fetchQuery={this.fetchQuery} />
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
                            {radicalList}
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

export default RadicalList;
