import React, { Component } from 'react'
import axios from 'axios'
import { Link } from 'react-router-dom';
import { connect } from "react-redux";
import { apiCall } from '../../services/api';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import AvatarImg from '../../assets/images/avatar-woman.svg';
import { hideLoader, showLoader } from "../../store/actions/application";

class ListDetails extends Component {
    constructor(props) {
        super(props);
        this.state = {
            list: null
        };

        this.likeList = this.likeList.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
        this.downloadPdf = this.downloadPdf.bind(this);
    };
  
  componentDidMount(){
    let id = this.props.match.params.list_id;
    axios.get('/api/list/' + id)
      .then(res => {
        this.setState({
          list: res.data.list
        });
        return res.data.list;
      })
      .then(list => {
        if(!list)
        {
            this.props.history.push('/lists');
        }
        // else if(this.props.currentUser.isAuthenticated)
        // {
        //   return apiCall("post", `/api/list/${id}/checklike`) TODO -
        //     .then(res => { 
        //       let newState = Object.assign({}, this.state);
        //       newState.article.isLiked = res.isLiked;
        //       this.setState(newState);
        //     });
        // }
      })
      .catch(err => {
          console.log(err);
      });
  };

  handleDelete(){
    console.log("deleted list: " + this.state.list.id);
    return apiCall("delete", `/api/list/${this.state.list.id}`)
      .then(res => { 
        console.log(res);
        this.props.history.push('/lists');
      })
      .catch(err => {
        console.log(err);
      });
      
  }

  addToList() {
      console.log("addToList");
  };

  downloadPdf() {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }
    else {
      this.props.dispatch( showLoader("Creating a PDF, please wait.") );

      let id = this.state.list.id;
      axios.get('/api/list/'+id+'/kanjis-pdf', {
        responseType: 'blob'
      })
      .then(res => {
        this.props.dispatch( hideLoader() );
        //Create a Blob from the PDF Stream
          const file = new Blob(
            [res.data], 
            {type: 'application/pdf'});
        //Build a URL from the file
          const fileURL = URL.createObjectURL(file);
        //Open the URL on new Window
          window.open(fileURL);
      })
      .catch(err => {
        console.log(err);
      })
    }

  };

  likeList() {
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }

    let endpoint = this.state.list.isLiked === true ? "unlike" : "like";
    let id = this.state.list.id;

    axios.post('/api/list/'+id+'/'+endpoint)
      .then(res => {
        let newState = Object.assign({}, this.state);

        if(endpoint === "unlike"){
            newState.list.isLiked = !newState.list.isLiked;
            newState.list.likesTotal -= 1;
            this.setState(newState);
        }
        else if (endpoint === "like"){
            newState.list.isLiked = !newState.list.isLiked;
            newState.list.likesTotal += 1;
            this.setState(newState);
        }
      })
      .catch(err => {
          console.log(err);
      })
  };

  render() {
    
    const { list } = this.state;
    const { currentUser } = this.props;

    const singleList = list ? (
      <div className="container">
          <div className="row justify-content-center">
              <div className="col-lg-8 ">
                <h1 className="mt-4">{list.title_jp}</h1>
                <p className="text-muted"> 
                    Posted on {list.jp_year} {list.jp_month} {list.jp_day} {list.jp_hour}
                    <br/><span>{list.viewsTotal + 40} views</span>
                    <span className="mr-1 float-right d-flex">
                        {currentUser.user.id === list.user_id ? (
                          <i className="far fa-trash-alt fa-lg" onClick={this.handleDelete}></i>
                        ) : ""}
                        {currentUser.user.id === list.user_id ?
                          (<Link to={`/list/edit/${list.id}`}><i className="far fa-edit ml-3 fa-lg"></i></Link>) : ""
                        }
                        <i onClick={this.addToList} className="far fa-bookmark ml-3 fa-lg"></i>
                        {/* { isBookmarked ? (<i class="fas fa-bookmark"></i>) } */}
                        <i onClick={this.downloadPdf} className="fas fa-file-download ml-3 fa-lg"></i>
                    </span>
                </p>
                <img className="img-fluid rounded mb-3" src={DefaultArticleImg} alt="default-article-img"/>
                <p className="lead">{list.content_jp} </p>
                <br/>
                <p>
                    {list.hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)}
                    <span className="mr-1 float-right d-flex text-muted">
                    {list.likesTotal+24} likes &nbsp;
                       {list.isLiked ? (<i onClick={this.likelist} className="fas fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       : (<i onClick={this.likelist} className="far fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       }
                    </span>
                </p>
                <hr/>
                <div className="text-muted">
                    <div className="mr-1 float-left d-flex">
                        <img src={AvatarImg} alt="book-japanese"/>
                        <p className="ml-3 mt-3">
                            created by {list.userName}
                        </p>
                    </div>
                </div>
              </div>
          </div>
      </div>
    ) : (
      <div className="center">Loading list...</div>
    );

    return (
      <div className="container">
        {singleList}
      </div>
    )
  }
}
const mapStateToProps = state => ({})

export default connect(mapStateToProps)(ListDetails);