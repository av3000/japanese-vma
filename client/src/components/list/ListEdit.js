import React, { Component } from 'react';
import { apiCall } from '../../services/api';
import { connect } from "react-redux";
import { hideLoader, showLoader } from "../../store/actions/application";

class ListEdit extends Component{
  constructor(props){
    super(props);
    this.state = {
        title: "",
        type: "",
        tags: "",
        publicity: ""
    }

    this.handleChange = this.handleChange.bind(this);
    this.onSubmit = this.onSubmit.bind(this)
  }

  componentWillMount(){
    this.getListDetails();
  }

  getListDetails(){
    let listId = this.props.match.params.list_id;
    return apiCall("get", `/api/list/${listId}`)
        .then(res => {
            
            let tags = "";
            res.list.hashtags.map(tag => tags += tag.content + " " );
            console.log(tags);
            this.setState({
                title: res.list.title,
                tags: tags,
                id: res.list.id
            }, () => {
                console.log(this.state);
            })
        })
        .catch(err => {
            console.log(err);
        });
    }


  onSubmit(e){
    e.preventDefault();
    let body = this.state.title;
    if(body.length < 3) {
        this.props.dispatch( showLoader("Title requires to be at least 3char long!") );
        setTimeout(() => {
            this.props.dispatch( hideLoader() )
        }, 2500);

        return;
    }

    this.props.dispatch( showLoader("Updating List, please wait.", "It will take a few seconds") );

    let payload = {
        title: this.state.title,
        publicity: this.state.publicity,
        tags: this.state.tags
    };

    console.log(payload);
    this.postNewList(payload);
  }

  postNewList(payload) {
    return apiCall('put', `/api/list/${this.state.id}`, payload)
    .then(res => {
        console.log("updatedList:")
        console.log(res);
        this.props.dispatch( hideLoader() );
        this.props.history.push("/list/"+res.updatedList.id);
    })
    .catch(err => {
        // let err = error.response.data.error;
        this.props.dispatch( hideLoader() );
        console.log(err);
        if(err.title)
        {
            console.log(err.title);
            // return err.title[0];
            return {success: false, err: err.title[0]};
        }
        else {
            console.log(err);
            return {success: false, err};
        }
    });
  }

  handleChange(e){
    this.setState({ [e.target.name]: e.target.value });
  }

  render(){
    return (

        <div className="container">
        <div className="row justify-content-md-center text-center">
            <form onSubmit={this.onSubmit}  className="col-12">
            {/* {this.props.errors.message && (
                <div className="alert alert-danger">{this.props.errors.message}</div>
            )} */}
            <label htmlFor="title" className="mt-3"> <h4>Title</h4> </label>
            <input
                placeholder="List title"
                type="text"
                className="form-control"
                value={this.state.title}
                name="title"
                onChange={this.handleChange}
            />
            <label htmlFor="tags" className="mt-3"> <h4>Add Tags</h4> </label>
            <input
                placeholder="#movie #booktitle #office"
                type="text"
                className="form-control"
                value={this.state.tags}
                name="tags"
                onChange={this.handleChange}
            />
            <label htmlFor="publicity" className="mt-3">Publicity</label>
            <select name="publicity" value={this.state.publicity} className="form-control" onChange={this.handleChange}>
                <option value="1">Public</option>
                <option value="0">Private</option>
            </select>
            <button type="submit" className="btn btn-outline-primary col-md-3 brand-button mt-5">
                Update the Article
            </button>
            </form>
        </div>
    </div>
    )
  }
}

const mapStateToProps = state => ({})

export default connect(mapStateToProps)(ListEdit);