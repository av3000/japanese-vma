import React, { Component } from "react";
import axios from "axios";
import { BASE_URL } from "../../shared/constants";

export default class CommentForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      isLoading: false,
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
      this.setState({ error: "Message is empty." });
      return;
    }

    this.setState({ error: "", isLoading: true });

    const { message } = this.state;
    const id = this.props.objectId;
    const objectType = this.props.objectType;
    const url = `${BASE_URL}/api/${objectType}/${id}/comment`;
    axios
      .post(url, {
        content: message,
      })
      .then((res) => {
        res.data.comment.userName = this.props.currentUser.user.name;
        this.props.addComment(res.data.comment);
        this.setState({
          isLoading: false,
          message: "",
        });
      })
      .catch((err) => {
        this.setState({ isLoading: false });
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
    const { isLoading } = this.state;
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
              disabled={isLoading}
              className="btn btn-outline-primary col-md-3 brand-button"
            >
              {isLoading ? (
                <span
                  className="spinner-border spinner-border-sm"
                  role="status"
                  aria-hidden="true"
                ></span>
              ) : (
                <span>
                  Comment
                  <i className="ml-2 fa-regular fa-paper-plane"></i>
                </span>
              )}
            </button>
          </div>
        </form>
      </React.Fragment>
    );
  }
}
