import React, { Component } from "react";
import axios from 'axios';

export default class CommentForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: false,
      error: "",
      message: "",
    };

    // bind context to methods
    this.handleChange = this.handleChange.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
  }
 
  handleChange(e){
    if(e.target.value.length > 1000) {
        e.target.value = e.target.value.substring(0, 1000);
    }
    this.setState({ [e.target.name]: e.target.value });
  }

  onSubmit(e) {
    // prevent default form submission
    e.preventDefault();
    
    if(!this.props.currentUser.isAuthenticated)
    {
      this.props.history.push('/login');
    }

    if (!this.isFormValid()) {
      this.setState({ error: "All fields are required." });
      return;
    }

    // loading status and clear error
    this.setState({ error: "", loading: true });

    // persist the comments on server
    let { message } = this.state;
    let id = this.props.objectId;
    let objectType = this.props.objectType;

    // axios.post
    axios.post(`/api/${objectType}/${id}/comment`, {
      content: message
    })
    .then(res => {
      res.data.comment.userName = this.props.currentUser.user.name;
      this.props.addComment(res.data.comment);
      // clear the message box
      this.setState({
        loading: false,
        message: ""
      });
    })
    .catch(err => {
        console.log("Oh no..");
        console.log(err);
    })
  }

  isFormValid() {
    return this.state.message !== "" ;
  }

  renderError() {
    return this.state.error ? (
      <div className="alert alert-danger">{this.state.error}</div>
    ) : null;
  }

  render() {
    return (
      <React.Fragment>
        <form method="post" onSubmit={this.onSubmit}>
          <div className="form-group">
            <textarea
              onChange={this.handleChange}
              value={this.state.message}
              className="form-control"
              placeholder="Your Comment"
              name="message"
              rows="5"
            />
          </div>

          {this.renderError()}

          <div className="form-group">
            <button disabled={this.state.loading} className="btn btn-outline-primary col-md-3 brand-button">
              Comment &#10148;
            </button>
          </div>
        </form>
      </React.Fragment>
    );
  }
}