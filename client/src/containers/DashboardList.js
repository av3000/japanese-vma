import React, { Component } from 'react';
import Moment from 'react-moment';
import { Link } from "react-router-dom";
import { apiCall } from '../services/api';
import DashboardArticleItem from '../components/dashboard/DashboardArticleItem';
import DashboardListItem from '../components/dashboard/DashboardListItem';
import Spinner from '../assets/images/spinner.gif';
import SearchBar from '../components/search/Searchbar';
import { Button, Modal } from 'react-bootstrap';


export class DashboardList extends Component {
    constructor(props){
        super(props);
        this.state = {
            whichResource: 0,
            lists: [],
            articles: [],
            articlesPending: [],
            dashboard: "user",
            filters:{}
            // showReviewModal: 0
        }

        this.fetchQuery = this.fetchQuery.bind(this);
        this.clearQuery = this.clearQuery.bind(this);
        this.toggleDashboard = this.toggleDashboard.bind(this);
        this.toggleResource = this.toggleResource.bind(this);
        // this.handleReviewModalClose = this.handleReviewModalClose.bind(this);
    };

    toggleResource(){
        let newState = Object.assign({}, this.state);
        newState.whichResource = newState.whichResource === 0 ? 1 : 0
        this.setState(newState);
    }

    toggleDashboard(){
        let newState = Object.assign({}, this.state);
        newState.dashboard = newState.dashboard === "user" ? "admin" : "user";
        this.setState(newState);

        if(this.state.dashboard === "admin") {
            this.fetchArticlesPending();
        }
    }

    componentDidMount() {
        if(this.props.currentUser.isAuthenticated){
            console.log("yes");
            this.fetchArticles();
            this.fetchLists();
        }
        else {
            this.props.history.push("/login");
        }
    };

    componentWillReceiveProps(nextProps) {
        // console.log("this.props");
        // console.log(this.props);
        // console.log("nextProps");
        // console.log(nextProps);
        // console.log("no")

        if(nextProps.currentUser.user.isAdmin){
            // console.log("yes")
            this.fetchArticlesPending();
        }
    }

    fetchArticlesPending() {
        return apiCall("get", `/api/articles/pendinglist`)
            .then(res => {
                // console.log(res);
                let newState = Object.assign({}, this.state);
                newState.articlesPending = res.articlesPending;

                this.setState( newState );
            })
            .catch(err => {
                console.log(err);
            })
    }

    fetchArticles(){
        return apiCall("get", `/api/user/articles`)
            .then(res => {
                // console.log(res);
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
                // console.log(res);
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

    // Reviewing

    // openReviewModal(articleId){
    //     console.log("openReviewModal");
    //     this.setState({showReviewModal: articleId});
    // }

    // handleReviewModalClose() {
    //     this.setState({showReviewModal: 0})
    // }

    toggleStatus(id, type){
        console.log("id article: " + id);
        console.log("type article: " + type);
        // console.log(this);
        return apiCall("post", `/api/article/${id}/setstatus`, {
                status: type
            })
            .then(() => {
                this.props.history.push(`/article/${id}`);
            })
            .catch(err => {
                console.log(err);
            })

    }

    render() {
        let { articles, lists, filters, articlesPending } = this.state;

        let { currentUser } = this.props;

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

        let articleListPending = articlesPending ? ( articlesPending.map(w => {

            return (
                <div className="row pb-3 mb-0 mt-3 border-bottom border-gray" key={w.id}>
                    <div className="col-lg-6">
                        <h4>
                            <Link to={`/article/${w.id}`}> {w.title_jp} </Link>
                        </h4>
                        <p>
                        tags: {w.hashtags.map(tag => <span key={tag.id} className="tag-link">{tag.content} </span>)}
                        </p>
                    </div>
                    <div className="col-lg-4 col-12-sm pt-3">
                        <small className="text-muted">
                                <span>
                                <Moment className="text-muted" format="Do MMM YYYY">
                                    {w.created_at}
                                </Moment>    
                                </span>
                                <br/>
                                <span> 
                                <Moment className="text-muted" date={w.created_at}
                                    durationFromNow
                                />
                                </span>
                        </small>
                    </div>
                    <div className="col-lg-2">
                        <button onClick={this.toggleStatus.bind(this, w.id, 1)} className="btn-sm btn-primary">
                            Review
                        </button>
                    </div>
                    {/* <Modal show={this.state.showReviewModal === w.id} onHide={this.handleReviewModalClose}>
                        <Modal.Header closeButton>
                            <Modal.Title>Are You Sure? </Modal.Title>
                        </Modal.Header>
                        <Modal.Footer>
                            <div className="col-12">
                            <Button variant="secondary" className="float-left" onClick={this.handleReviewModalClose}>
                                Cancel
                            </Button>
                            <Button variant="danger" className="float-right" onClick={this.toggleStatus.bind(this, w.id, 1)}>
                                Review
                            </Button>
                            </div>
                        </Modal.Footer>
                    </Modal> */}
                </div>
            );
                
        }) ): (
        <div className="container mt-5">
            <div className="row justify-content-center">
                <img src={Spinner}/>
            </div>
        </div>
        );
        
        let customList = this.state.lists ? 
            ( 
                this.state.lists.map(w => {
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
                    {/* <SearchBar fetchQuery={this.fetchQuery} /> */}
                </div>
                <div className="container mt-5">
                    <div className="ml-3 mt-2">
                        { 
                        this.state.whichResource === 0 ? 
                        (
                            <button className="btn btn-sm btn-light brand-button" onClick={this.toggleResource}>Articles <i className="fas fa-arrow-right"></i></button>
                        ) 
                        : 
                        (
                            <button className="btn btn-light brand-button" onClick={this.toggleResource}>Lists <i className="fas fa-arrow-right"></i></button>
                        )}
                    </div>
                    { this.state.whichResource === 0 ? 
                        (
                            <div className="my-3 p-3 bg-white rounded box-shadow">
                                <h4 className="border-bottom border-gray pb-2 mb-0">Your Lists</h4>
                                <div className="col-lg-12 col-md-10 mx-auto">
                                    { this.state.lists && customList ? customList : (
                                        <div className="container mt-5">
                                            <div className="row justify-content-center">
                                                <img src={Spinner}/>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )
                    :   
                        (
                            <div className="my-3 p-3 bg-white rounded box-shadow">
                                {
                                    this.state.articles ? 
                                    (
                                            this.state.dashboard === "admin" ? 
                                        (
                                            <React.Fragment>
                                                    <div className="d-flex justify-content-between align-items-center">
                                                        <h4>Admin view</h4>
                                                        <button className="btn btn-sm btn-light" onClick={this.toggleDashboard}>
                                                            User <i className="fas fa-arrow-right"></i>
                                                        </button>
                                                    </div>
                                                
                                                <div className="col-lg-12 col-md-10 mx-auto">
                                                    {articleListPending}
                                                </div>
                                            </React.Fragment>
                                            
                                        ) : (
                                            <React.Fragment>
                                                <div className="d-flex justify-content-between align-items-center">
                                                    <h4>User view</h4>
                                                    <button className="btn btn-sm btn-light" onClick={this.toggleDashboard}>
                                                        Admin <i className="fas fa-arrow-right"></i>
                                                    </button>
                                                </div>
                                                <div className="col-lg-12 col-md-10 mx-auto">
                                                    {articleList}
                                                </div>
                                            </React.Fragment>
                                        )
                                    ) : ""
                                }
                            </div>
                        )
                    }
                </div>
            </div>
        )
    }
}

export default DashboardList;
