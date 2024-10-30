import React, { Component } from "react";
import axios from "axios";
import { BASE_URL } from "../../shared/constants";

export default class CommentForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: false,
      error: "",
      message: "",
    };

    this.handleChange = this.handleChange.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
  }

  handleChange(e) {
    if (e.target.value.length > 1000) {
      e.target.value = e.target.value.substring(0, 1000);
    }
    this.setState({ [e.target.name]: e.target.value });
  }

  onSubmit(e) {
    e.preventDefault();

    if (!this.props.currentUser.isAuthenticated) {
      this.props.history.push("/login");
    }

    if (!this.isFormValid()) {
      this.setState({ error: "All fields are required." });
      return;
    }

    this.setState({ error: "", loading: true });

    const { message } = this.state;
    const id = this.props.objectId;
    const objectType = this.props.objectType;
    const url = BASE_URL + `/api/${objectType}/${id}/comment`;
    axios
      .post(url, {
        content: message,
      })
      .then((res) => {
        res.data.comment.userName = this.props.currentUser.user.name;
        this.props.addComment(res.data.comment);
        this.setState({
          loading: false,
          message: "",
        });
      })
      .catch((err) => {
        console.log(err);
      });
  }

  isFormValid() {
    return this.state.message !== "";
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
            <button
              disabled={this.state.loading}
              className="btn btn-outline-primary col-md-3 brand-button"
            >
              Comment &#10148;
            </button>
          </div>
        </form>
      </React.Fragment>
    );
  }
}
