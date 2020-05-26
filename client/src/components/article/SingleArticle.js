import React, { Component } from 'react'
import axios from 'axios'
import { Link } from 'react-router-dom';
import { apiCall } from "../../services/api"; 
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import DownloadsImg from '../../assets/icons/download-icon.svg';
import CommentsImg from '../../assets/icons/comments-icon.svg';
import BookmarkImg from '../../assets/icons/bookmark-icon.svg';
import LikesImg from '../../assets/icons/like-icon.svg';
import AvatarImg from '../../assets/images/avatar-woman.svg';

class SingleArticle extends Component {
    constructor(props) {
        super(props);
        this.state = {
            article: null
        };

        this.likeArticle = this.likeArticle.bind(this);
    };
  
  componentDidMount(){
    let id = this.props.match.params.article_id;
    axios.get('/api/article/' + id)
      .then(res => {
        this.setState({
          article: res.data.article
        });

        return res.data.article;
      })
      .then(article => {
        if(!article)
        {
            this.props.history.push('/login');
        }

        if(this.props.currentUser.isAuthenticated)
        {
            // return apiCall("post", `/api/article/${id}/checklike`, )
            // check if user likes this article
            // if yes, return isLiked: true
            
            // plan B. Analize other apps authorization on creating resources 

            // plan C. Modify '/api/article/{id}' and add optional param 'user_id'
            // if $request->user_id -> then do CheckIfLikedArticle($id, $user_id)

        }
      })
      .catch(err => {
          console.log(err);
      });

  };

  addToList() {
      console.log("addToList");
  };

  downloadPdf() {
    console.log("downloadPdf");
  };

  likeArticle() {
    
    let endpoint = this.state.isLiked == true ? "unlike" : "like";
    console.log("endpoint: " + endpoint);
    console.log("isLiked: " + this.state.isLiked);
    let id = this.state.article.id;
    axios.post('/api/article/'+id+'/'+endpoint)
        .then(res => {
            console.log(res);
            let newState = Object.assign({}, this.state);
            if(endpoint){
                newState.article.isLiked = !newState.article.isLiked;
                newState.article.likesTotal -= 1;
                this.setState(newState);
            }
            else {
                newState.article.isLiked = !newState.article.isLiked;
                newState.article.likesTotal += 1;
                this.setState(newState);
            }
            
        })
        .catch(err => {
            console.log(err);
        })
  };

  handleLike() {

  }

  
  render() {
    const { article } = this.state;
    console.log(article);
    const singleArticle = article ? (
      <div className="container">
          <div className="row justify-content-center">
              <div className="col-lg-8 ">
                <h1 className="mt-4">{article.title_jp}</h1>
                <p className="text-muted"> 
                    Posted on {article.jp_year} {article.jp_month} {article.jp_day} {article.jp_hour}
                    <br/><span>{article.viewsTotal + 40} views</span>
                    <span className="mr-1 float-right d-flex">
                        <img onClick={this.addToList} src={BookmarkImg} className="ml-2" alt=""/>
                        <img onClick={this.downloadPdf} src={DownloadsImg} className="ml-2" alt=""/>
                    </span>
                </p>
                <img className="img-fluid rounded mb-3" src={DefaultArticleImg} alt="default-article-img"/>
                <p className="lead">{article.content_jp} </p>
                <br/>
                <p>
                    {article.hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)}
                    <span className="mr-1 float-right d-flex text-muted">
                    {article.likesTotal+24} likes &nbsp;<img onClick={this.likeArticle} src={LikesImg} className="ml-1 mr-1" alt=""/>
                    </span>
                </p>
                <hr/>
                <div className="text-muted">
                    <div className="mr-1 float-left d-flex">
                        <img src={AvatarImg} alt="default-avatar-image"/>
                        <p className="ml-3 mt-3">
                            uploaded by {article.userName}
                        </p>
                    </div>
                    <div className="mr-1 mt-3 float-right d-flex">
                        <Link to={article.source_link}> original source</Link>
                    </div>
                </div>
              </div>
          </div>
      </div>
    ) : (
      <div className="center">Loading article...</div>
    );

    return (
      <div className="container">
        {singleArticle}
      </div>
    )
  }
}

export default SingleArticle;