import React, { Component } from "react";
import { connect } from "react-redux";
import { apiCall } from "../../services/api";
import { hideLoader, showLoader } from "../../store/actions/application";

class ListForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
      title: "",
      type: 5,
      tags: "",
      publicity: false,
    };

    this.handleChange = this.handleChange.bind(this);
  }

  handleNewList = (e) => {
    e.preventDefault();

    let body = this.state.title;
    if (body.length < 3) {
      this.props.dispatch(
        showLoader("Title requires to be at least 3char long!")
      );
      setTimeout(() => {
        this.props.dispatch(hideLoader());
      }, 2500);

      return;
    }

    this.props.dispatch(
      showLoader("Creating List, please wait.", "It will take a few seconds")
    );

    let payload = {
      title: this.state.title,
      type: this.state.type,
      tags: this.state.tags,
      publicity: this.state.publicity,
    };

    this.postNewList(payload);
  };

  postNewList(payload) {
    return apiCall("post", `/api/list`, payload)
      .then((res) => {
        this.props.dispatch(hideLoader());
        this.props.history.push("/list/" + res.newList.id);
      })
      .catch((err) => {
        this.props.dispatch(hideLoader());
        console.log(err);
        if (err.title) {
          return { success: false, err: err.title[0] };
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
          <form onSubmit={this.handleNewList} className="col-12">
            {this.props.errors.message && (
              <div className="alert alert-danger">
                {this.props.errors.message}
              </div>
            )}
            <label htmlFor="title" className="mt-3">
              {" "}
              <h4>Title</h4>{" "}
            </label>
            <input
              placeholder="List title"
              type="text"
              className="form-control"
              value={this.state.title}
              name="title"
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
            <label htmlFor="type" className="mt-3">
              List Type
            </label>
            <select
              name="type"
              value={this.state.type}
              className="form-control"
              onChange={this.handleChange}
            >
              <option value="5">Radicals</option>
              <option value="6">Kanjis</option>
              <option value="7">Words</option>
              <option value="8">Sentences</option>
              <option value="9">Articles</option>
            </select>
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
            <button
              type="submit"
              className="btn btn-outline-primary col-md-3 brand-button mt-5"
            >
              Create the List
            </button>
          </form>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => ({});

export default connect(mapStateToProps)(ListForm);
