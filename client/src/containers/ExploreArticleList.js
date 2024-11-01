import React, { Component } from "react";
import { connect } from "react-redux";
import { Link } from "react-router-dom";
import { fetchArticles } from "../store/actions/articles";
import ExploreArticleItem from "../components/article/ExploreArticleItem";
import Spinner from "../assets/images/spinner.gif";

class ExploreArticleList extends Component {
  componentDidMount() {
    if (!this.props.articles.length) {
      this.props.fetchArticles();
    }
  }

  render() {
    const { articles, isLoading, paginationInfo } = this.props;

    const featuredArticles = articles
      .slice(0, 3)
      .map((a) => <ExploreArticleItem key={a.id} {...a} />);

    if (isLoading) {
      return (
        <div className="d-flex justify-content-center w-100">
          <img src={Spinner} alt="spinner loading" />
        </div>
      );
    }

    return (
      <React.Fragment>
        <div className="d-flex justify-content-between align-items-center w-100 my-3">
          <h3>Latest Articles ({paginationInfo.total || 0})</h3>
          <div>
            <Link to="/articles" className="homepage-section-title">
              Read All Articles
            </Link>
          </div>
        </div>
        <div className="row">{featuredArticles}</div>
      </React.Fragment>
    );
  }
}

const mapStateToProps = (state) => ({
  articles: state.articles.articles,
  isLoading: state.articles.isLoading,
  paginationInfo: state.articles.paginationInfo,
});

const mapDispatchToProps = {
  fetchArticles,
};

export default connect(mapStateToProps, mapDispatchToProps)(ExploreArticleList);
