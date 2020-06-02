import React, { Component } from 'react';
import { connect } from 'react-redux';
// import { fetchArticles, removeArticle } from '../store/actions/articles';
import { apiCall } from '../services/api';
import ArticleItem from '../components/article/ArticleItem';
import Spinner from '../assets/images/spinner.gif';
import SearchBar from '../components/search/Searchbar';

class ArticleList extends Component {
    constructor(){
        super();
        this.state = {
            url: '/api/articles',
            pagination: [],
            articles: [],
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
        this.fetchArticles(this.state.url);
    };

    fetchQuery(queryParams) {
        let newState = Object.assign({}, this.state);
        newState.filters = queryParams;
        apiCall("post", "/api/articles/search", newState.filters)
        .then(res => {
            if(res.success === true)
            {
                newState.paginateObject = res.articles;
                newState.articles       = res.articles.data ? res.articles.data : newState.articles;
                newState.url            = res.articles.next_page_url;

                newState.searchHeading = "Requested query: " + newState.filters.title;
                newState.searchTotal = "results total: " + res.articles.total;
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

    fetchArticles(givenUrl){
        return apiCall("get", givenUrl)
            .then(res => {
                let newState = Object.assign({}, this.state);
                newState.paginateObject = res.articles;
                newState.articles = [...newState.articles, ...res.articles.data];
                newState.url = res.articles.next_page_url;

                newState.searchTotal = "results total: " + res.articles.total;

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
            console.log(res);

            newState.paginateObject = res.articles;
            newState.articles       = [...newState.articles, ...res.articles.data];
            newState.url            = res.articles.next_page_url;

            newState.searchHeading = "Requested query: " + newState.filters.title;
            newState.searchTotal = "results total: " + res.articles.total;

            return newState;
        })
        .then(newState => {
            newState.pagination = this.makePagination(newState.paginateObject);

            this.setState( newState );

            console.log(this.state.articles);
        })
        .catch(err => {
            console.log(err);
        })
    }

    loadMore() {
        this.fetchArticles(this.state.pagination.next_page_url);
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

        const { currentUser } = this.props;
        let { articles } = this.state;

        let articleList = articles ? ( articles.map(a => (
                <ArticleItem
                    key={a.id}
                    id={a.id}
                    created_at={a.created_at}
                    jp_year={a.jp_year}
                    jp_month={a.jp_month}
                    jp_day={a.jp_day}
                    jp_hour={a.jp_hour}
                    title_jp={a.title_jp}
                    commentsTotal={a.commentsTotal}
                    likesTotal={a.likesTotal}
                    viewsTotal={a.viewsTotal}
                    downloadsTotal={a.downloadsTotal}
                    hashtags={a.hashtags.slice(0, 3)}
                    // bookmarkArticle={bookmarkArticle.bind(this, a.id)}
                    // removeArticle={removeArticle.bind(this, a.id)}
                    // isCorrectUser={currentUser === a.user_id}
                />
        )) ) : (
        <div className="container">
            <div className="row justify-content-center">
                <img src={Spinner}/>
            </div>
        </div>
        );
        
        return (
            <div className="container mt-5">
                <div className="">
                    <SearchBar fetchQuery={this.fetchQuery} articles={this.state.articles}/>
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
                     {articleList}
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
//         articles: state.articles
//     };
// };

// export default connect(mapStateToProps, { fetchArticles })(ArticleList);
export default ArticleList;