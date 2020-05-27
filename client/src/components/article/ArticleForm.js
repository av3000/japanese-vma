import React, { Component } from "react";
import { connect } from "react-redux";
import { postNewArticle } from "../../store/actions/articles";

class ArticleForm extends Component {
  constructor(props) {
    super(props);
    this.state = {
        title_jp: "",
        title_en: "",
        content_en: "",
        content_jp: "",
        source_link: "",
        publicity: false
    };
  }

  handleNewArticle = e => {
    e.preventDefault();
    console.log(this.state);
    // this.props.postNewArticle(this.state);
    // this.setState({ message: "" });
    // this.props.history.push("/");
  };

  render() {
    return (
        <div>
            <form onSubmit={this.handleNewArticle}></form>
            {this.props.errors.message && (
                <div className="alert alert-danger">{this.props.errors.message}</div>
            )}
            <button type="submit" className="btn btn-success">
                Post the Article
            </button>
        </div>
    //   <form onSubmit={this.handleNewArticle}>
    //     {this.props.errors.message && (
    //       <div className="alert alert-danger">{this.props.errors.message}</div>
    //     )}
    //     <input
    //       type="text"
    //       className="form-control"
    //       value={this.state.message}
    //       onChange={e => this.setState({ message: e.target.value })}
    //     />
    //     <button type="submit" className="btn btn-success">
    //       Post the Article
    //     </button>
    //   </form>
    );
  }
}

function mapStateToProps(state) {
  return {
    errors: state.errors
  };
}

export default connect(mapStateToProps, { postNewArticle })(ArticleForm);