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
            return apiCall("post", `/api/article/${id}/checklike`)
              .then(res=> { 
                let newState = Object.assign({}, this.state);
                console.log(res)
                newState.article.isLiked = res.isLiked;
                this.setState(newState);
               });
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
    
    let endpoint = this.state.article.isLiked == true ? "unlike" : "like";
    console.log("endpoint: " + endpoint);
    console.log("isLiked: " + this.state.article.isLiked);
    let id = this.state.article.id;
    axios.post('/api/article/'+id+'/'+endpoint)
        .then(res => {
            console.log(res);
            let newState = Object.assign({}, this.state);
            if(endpoint == "unlike"){
                newState.article.isLiked = !newState.article.isLiked;
                newState.article.likesTotal -= 1;
                this.setState(newState);
            }
            else if (endpoint == "like"){
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
                        <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg"></i>
                        {/* { isBookmarked ? (<i class="fas fa-bookmark"></i>) } */}
                        <i onClick={this.downloadPdf} className="fas fa-file-download ml-3 fa-lg"></i>
                    </span>
                </p>
                <img className="img-fluid rounded mb-3" src={DefaultArticleImg} alt="default-article-img"/>
                <p className="lead">{article.content_jp} </p>
                <br/>
                <p>
                    {article.hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)}
                    <span className="mr-1 float-right d-flex text-muted">
                    {article.likesTotal+24} likes &nbsp;
                       {article.isLiked ? (<i onClick={this.likeArticle} className="fas fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       : (<i onClick={this.likeArticle} className="far fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       }
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

const likeStyle = {
  border: "1px solid orange",
  borderRadius: "30%"
};

export default SingleArticle;