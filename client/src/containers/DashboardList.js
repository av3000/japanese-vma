import React, { Component } from 'react';
import { apiCall } from '../services/api';
import DashboardArticleItem from '../components/dashboard/DashboardArticleItem';
import DashboardListItem from '../components/dashboard/DashboardListItem';
import Spinner from '../assets/images/spinner.gif';
import SearchBar from '../components/search/Searchbar';


export class DashboardList extends Component {
    constructor(props){
        super(props);
        this.state = {
            whichResource: 0,
            lists: [],
            articles: [],
            filters:{}
        }
        this.fetchQuery = this.fetchQuery.bind(this);
        this.clearQuery = this.clearQuery.bind(this);
        this.toggleDashboard = this.toggleDashboard.bind(this);
    };

    toggleDashboard(){
        let newState = Object.assign({}, this.state);
        newState.whichResource = newState.whichResource === 0 ? 1 : 0
        this.setState(newState);
    }

    componentDidMount() {
        this.fetchArticles();
        this.fetchLists();
    };

    fetchArticles(){
        return apiCall("get", `/api/user/articles`)
            .then(res => {
                console.log(res);
                let newState = Object.assign({}, this.state);
                newState.articles = [...newState.articles, ...res.articles];

                this.setState( newState );
                return newState;
            })
            .catch(err => {
                console.log(err);
            })
    };

    fetchLists(){
        return apiCall("get", `/api/user/lists`)
            .then(res => {
                console.log(res);
                let newState = Object.assign({}, this.state);
                newState.lists = [...newState.lists, ...res.lists];
                this.setState( newState );
            })
            .catch(err => {
                console.log(err);
            })
    };

    fetchQuery(queryParams){   
        let newState = Object.assign({}, this.state);

        newState.filters.text = queryParams.title;

        // let filteredLists = lists.filter(
        //     (list) => { return list.title.indexOf(filters.text) !== -1; }
        // );

        this.setState(newState);
    }


    clearQuery(){
        let newState = Object.assign({}, this.setState);
        newState.filters = {};
        this.setState(newState);
    }

    render() {
        let { articles, lists, filters } = this.state;

        let articleList = articles ? ( articles.map(w => {

            return (
                <DashboardArticleItem 
                    key={w.id}
                    id={w.id}
                    title={w.title_jp}
                    publicity={w.publicity}
                    commentsTotal={w.commentsTotal}
                    likesTotal={w.likesTotal}
                    viewsTotal={w.viewsTotal}
                    downloadsTotal={w.downloadsTotal}
                    hashtags={w.hashtags}
                    currentUser={this.props.currentUser}
                />
            );
                
        }) ): (
        <div className="container mt-5">
            <div className="row justify-content-center">
                <img src={Spinner}/>
            </div>
        </div>
        );
        
        let customList = lists ? 
            ( 
                lists.map(w => {
                    return (
                        <DashboardListItem 
                            key={w.id}
                            id={w.id}
                            title={w.title}
                            publicity={w.publicity}
                            type={w.type}
                            listType={w.typeTitle}
                            commentsTotal={w.commentsTotal}
                            likesTotal={w.likesTotal}
                            viewsTotal={w.viewsTotal}
                            downloadsTotal={w.downloadsTotal}
                            hashtags={w.hashtags.slice(0, 3)}
                            user={w.user}
                            postType={w.postType}
                            currentUser={this.props.currentUser}
                            deleteList={this.deleteList}
                            editList={this.editList}
                        />
                    );
                })
            )
            : 
            (
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
                </div>
                <div className="container mt-5">
                    <div className="ml-3 mt-2">
                        { 
                        this.state.whichResource === 0 ? 
                        (
                            <button className="btn btn-sm btn-light brand-button" onClick={this.toggleDashboard}>Articles <i className="fas fa-arrow-right"></i></button>
                        ) 
                        : 
                        (
                            <button className="btn btn-light brand-button" onClick={this.toggleDashboard}>Lists <i className="fas fa-arrow-right"></i></button>
                        )}
                    </div>
                    { this.state.whichResource === 0 ? 
                    
                        (<div className="my-3 p-3 bg-white rounded box-shadow">
                        <h4 className="border-bottom border-gray pb-2 mb-0">Your Lists</h4>
                        <div className="col-lg-12 col-md-10 mx-auto">
                            {customList}
                        </div>
                    </div>)
                : (
                    <div className="my-3 p-3 bg-white rounded box-shadow">
                        <h4 className="border-bottom border-gray pb-2 mb-0">Your Articles</h4>
                        <div className="col-lg-12 col-md-10 mx-auto">
                            {articleList}
                        </div>
                    </div>
                )}
                    
                    
                </div>
                {/* <div className="row justify-content-center">
                { this.state.pagination.last_page === this.state.pagination.current_page ? 
                    "no more results..." 
                        : 
                    this.state.url.includes("search") ? 
                        (<button className="btn btn-outline-primary brand-button col-6" onClick={this.loadSearchMore}>Load More</button>) 
                        :
                        (<button className="btn btn-outline-primary brand-button col-6" onClick={this.loadMore}>Load More</button>)
                
                }
                </div> */}
            </div>
        )
    }
}

export default DashboardList;
