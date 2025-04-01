// @ts-nocheck
import React, { Component } from "react";
import { apiCall } from "../../services/api";
import "./ArticleForm.css";
import { hideLoader, showLoader } from "../../store/actions/application";
import { Button } from "@/components/shared/Button";

class ArticleForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      title_jp: "",
      title_en: "",
      content_en: "",
      content_jp: "",
      source_link: "",
      tags: "",
      publicity: true,
      isLoading: false,
    };

    this.handleChange = this.handleChange.bind(this);
  }

  handleNewArticle = (e) => {
    e.preventDefault();

    let body = this.state.content_jp + this.state.title_jp;
    if (body.length < 4) {
      this.props.dispatch(showLoader("Fields are not filled properly!"));
      setTimeout(() => {
        this.props.dispatch(hideLoader());
      }, 3000);

      return;
    }

    let digit = Math.ceil(body.length / 100); // 100chars = 1min
    let approxText = "It should take up to " + digit + " minutes.";
    this.props.dispatch(
      showLoader("Creating Article, please wait.", approxText)
    );

    let payload = {
      title_jp: this.state.title_jp,
      content_jp: this.state.content_jp,
      source_link: this.state.source_link,
      tags: this.state.tags,
      publicity: this.state.publicity,
      attach: 1,
    };

    this.postNewArticle(payload);
  };

  postNewArticle(payload) {
    this.setState({ isLoading: true });

    return apiCall("post", `/api/article`, payload)
      .then((res) => {
        this.props.dispatch(hideLoader());
        this.setState({ isLoading: false });
        this.props.history.push("/article/" + res.article.id);
      })
      .catch((err) => {
        this.props.dispatch(hideLoader());
        this.setState({ isLoading: false });
        if (err.title_jp) {
          return { success: false, err: err.title_jp[0] };
        } else if (err.content_jp) {
          return { success: false, err: err.content_jp[0] };
        } else if (err.source_link) {
          return { success: false, err: err.source_link[0] };
        } else {
          console.log(err);
          return { success: false, err };
        }
      });
  }

  handleChange(e) {
    this.setState({ [e.target.name]: e.target.value });
  }

  render() {
    return (
      <div className="container">
        <div className="row justify-content-lg-center text-center">
          <form onSubmit={this.handleNewArticle} className="col-12">
            <label htmlFor="content_jp" className="mt-3">
              {" "}
              <h4>Title</h4>{" "}
            </label>
            <input
              placeholder="Article title text"
              type="text"
              className="form-control"
              value={this.state.title_jp}
              name="title_jp"
              onChange={this.handleChange}
            />
            <label htmlFor="content_jp" className="mt-3">
              {" "}
              <h4>Content</h4>{" "}
            </label>
            <textarea
              placeholder="Article body text"
              type="text"
              className="form-control resize-none"
              value={this.state.content_jp}
              name="content_jp"
              onChange={this.handleChange}
              rows="7"
            ></textarea>
            <label htmlFor="content_jp" className="mt-3">
              {" "}
              <h4>Source Link</h4>{" "}
            </label>
            <input
              placeholder="https://jplearning.online/article/title..."
              type="text"
              className="form-control"
              value={this.state.source_link}
              name="source_link"
              onChange={this.handleChange}
            />
            <label htmlFor="tags" className="mt-3">
              {" "}
              <h4>Add Tags</h4>{" "}
            </label>
            <input
              placeholder="#movie #booktitle #office"
              type="text"
              className="form-control"
              value={this.state.tags}
              name="tags"
              onChange={this.handleChange}
            />
            <label htmlFor="publicity" className="mt-3">
              Publicity
            </label>
            <select
              name="publicity"
              value={this.state.publicity}
              className="form-control"
              onChange={this.handleChange}
            >
              <option value="1">Public</option>
              <option value="0">Private</option>
            </select>
            <Button type="submit" variant="outline">
              {this.state.isLoading ? (
                <span
                  className="spinner-border spinner-border-sm"
                  role="status"
                  aria-hidden="true"
                ></span>
              ) : (
                <span>Create</span>
              )}
            </Button>
          </form>
        </div>
      </div>
    );
  }
}

export default ArticleForm;
