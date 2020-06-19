import React, { Component } from 'react';
// import { connect } from 'react-redux';
// import { fetchLists } from '../store/actions/lists';
import ListItem from '../components/list/ListItem';
import { apiCall } from '../services/api';
import Spinner from '../assets/images/spinner.gif';
import SearchBar from '../components/search/Searchbar';


class ListsList extends Component {
    constructor(){
        super();
        this.state = {
            url: '/api/lists',
            pagination: [],
            lists: [],
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
        this.fetchLists(this.state.url);
    };

    fetchQuery(queryParams) {
        let newState = Object.assign({}, this.state);
        newState.filters = queryParams;
        apiCall("post", "/api/lists/search", newState.filters)
        .then(res => {
            if(res.success === true)
            {
                newState.paginateObject = res.lists;
                newState.lists       = res.lists.data ? res.lists.data : newState.lists;
                newState.url            = res.lists.next_page_url;

                newState.searchHeading = res.requestedQuery;
                newState.searchTotal = "results total: " + res.lists.total;
                return newState;
            }

        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );
        })
        .catch(err => {
            this.setState( newState );
            console.log(err);
        })
    }

    fetchLists(givenUrl){
        return apiCall("get", givenUrl)
            .then(res => {
                let newState = Object.assign({}, this.state);
                newState.paginateObject = res.lists;
                newState.lists = [...newState.lists, ...res.lists.data];
                newState.url = res.lists.next_page_url;

                newState.searchTotal = "results total: " + res.lists.total;

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

    fetchMoreQuery(givenUrl) {
        let newState = Object.assign({}, this.state);
        apiCall("post", givenUrl, newState.filters)
        .then(res => {
            newState.paginateObject = res.lists;
            newState.lists       = [...newState.lists, ...res.lists.data];
            newState.url            = res.lists.next_page_url;

            newState.searchTotal = "results total: " + res.lists.total;

            return newState;
        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );
        })
        .catch(err => {
            console.log(err);
        })
    }

    loadMore() {
        this.fetchLists(this.state.pagination.next_page_url);
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


    render() {
        const listTypes = [
            "knownradicals list",
            'knownkanjis list',
            'knownwords list',
            'knownsentences list',
            'Radicals List',
            'Kanjis List',
            'Words List',
            'Sentences List',
            'Articles List'
        ];

        let { lists } = this.state;
        let customLists = lists ? (lists.map(l => (
            <ListItem
                key={l.id}
                id={l.id}
                type={l.type}
                listType={listTypes[l.type-1]}
                created_at={l.created_at}
                title={l.title}
                commentsTotal={l.commentsTotal}
                itemsTotal={l.listItems.length}
                likesTotal={l.likesTotal}
                viewsTotal={l.viewsTotal}
                downloadsTotal={l.downloadsTotal}
                hashtags={l.hashtags.slice(0, 3)}
                listItems={l.listItems}
                n1={l.n1}
                n2={l.n2}
                n3={l.n3}
                n4={l.n4}
                n5={l.n5}
                uncommon={l.uncommon}
            />
        )) ) : (
            <div className="container">
                <div className="row justify-content-center">
                    <img src={Spinner} alt="spinner"/>
                </div>
            </div>
        )

        return (
            <div className="container mt-5">
                <div className="">
                    <SearchBar fetchQuery={this.fetchQuery} searchType="lists"/>
                    {/* by tag */}
                    {/* by title keyword */}
                    {/* by newest/popular */}
                </div>
                <div className="container mt-5">
                    {this.state.searchHeading ? (
                        <h4>
                            {this.state.searchHeading}
                        </h4> 
                        )
                        : ""
                    }  
                    
                    {this.state.searchTotal ? (
                        <h4>
                            {this.state.searchTotal}
                        </h4> 
                        )
                        : ""
                    }
                    <div className="row">
                     {customLists}
                    </div>
                </div>
                <div className="row justify-content-center ">
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
        );
    }
}

// function mapStateToProps(state) {
//     return {
//         lists: state.lists
//     };
// };

// export default connect(mapStateToProps, { fetchLists })(ListsList);
export default ListsList;