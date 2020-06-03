import React, { Component } from 'react';
import { apiCall } from '../services/api';
import PostItem from '../components/post/PostItem';
import Spinner from '../assets/images/spinner.gif';
import SearchBar from '../components/search/Searchbar';


export class PostList extends Component {
    _isMounted = false;
    constructor(props){
        super(props);
        this.state = {
            url: '/api/posts',
            pagination: [],
            posts: [],
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
        this._isMounted = true;
        this.fetchPosts(this.state.url);
    };

    componentWillUnmount() {
        this._isMounted = false;
    };

    fetchPosts(givenUrl){
        return apiCall("get", givenUrl)
            .then(res => {
                if (this._isMounted) {
                    console.log(res);
                    let newState = Object.assign({}, this.state);
                    newState.paginateObject = res.posts;
                    newState.posts = [...newState.posts, ...res.posts.data];
                    newState.url = res.posts.next_page_url;

                    newState.searchTotal = "results total: '" + res.posts.total + "'";

                    return newState;
                }
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
        apiCall("post", "/api/posts/search", newState.filters)
        .then(res => {
            if(res.success === true)
            {
                newState.paginateObject = res.posts;
                newState.posts       = res.posts.data ? res.posts.data : newState.posts;
                newState.url            = res.posts.next_page_url;

                newState.searchHeading = "Requested query: '" + newState.filters.title +"'";
                newState.searchTotal = "Results total: '" + res.posts.total +"'";
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

            newState.paginateObject = res.posts;
            newState.posts         = [...newState.posts, ...res.posts.data];
            newState.url            = res.posts.next_page_url;

            newState.searchHeading = "Requested query: '" + newState.filters.title +"'";
            newState.searchTotal = "Results total: '" + res.posts.total +"'";

            return newState;
        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );

            console.log(this.state.posts);
        })
        .catch(err => {
            console.log(err);
        })
    }

    loadMore() {
        this.fetchPosts(this.state.pagination.next_page_url);
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
        let { posts } = this.state;
        let postList = posts ? ( posts.map(w => {

            return (
                <PostItem 
                    key={w.id}
                    id={w.id}
                    title={w.title}
                    date={w.created_at}
                    content={w.content}
                    type={w.type}
                    locked={w.locked}
                    userId={w.user_id}
                    commentsTotal={w.commentsTotal}
                    likesTotal={w.likesTotal}
                    viewsTotal={w.viewsTotal}
                    downloadsTotal={w.downloadsTotal}
                    hashtags={w.hashtags.slice(0, 3)}
                    user={w.user}
                    postType={w.postType}
                    // currentUser={this.props.currentUser}
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
                    <div className="my-3 p-3 bg-white rounded box-shadow">
                        <h6 className="border-bottom border-gray pb-2 mb-0">Newest Topics</h6>
                        <div className="col-lg-12 col-md-10 mx-auto">
                            {postList}
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

export default PostList;
