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

    const featuredArticles = articles.slice(0, 3);

    return (
      <React.Fragment>
        <h3>
          <span>Latest Articles ({paginationInfo.total || 0})</span>
          <Link to="/articles" className="homepage-section-title">
            Read All
          </Link>
        </h3>
        <div className="row">
          {isLoading ? (
            <div className="container">
              <div className="row justify-content-center">
                <img src={Spinner} alt="Loading..." />
              </div>
            </div>
          ) : featuredArticles.length ? (
            featuredArticles.map((a) => (
              <ExploreArticleItem key={a.id} {...a} />
            ))
          ) : (
            <p className="text-center">No Featured Articles available.</p>
          )}
        </div>
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
