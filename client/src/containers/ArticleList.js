import React, { Component } from "react";
import { connect } from "react-redux";
import { fetchArticles, setSelectedArticle } from "../store/actions/articles";
import ArticleItem from "../components/article/ArticleItem";
import Spinner from "../assets/images/spinner.gif";
import SearchBar from "../components/search/Searchbar";

class ArticleList extends Component {
  constructor(props) {
    super(props);
    this.state = {
      searchHeading: "",
      searchTotal: "",
      filters: {},
    };
  }

  componentDidMount() {
    if (!this.props.articles.length) {
      this.props.fetchArticles();
    }
  }

  applyFilters = (filters) => {
    this.setState({ filters });
    this.props.fetchArticles(filters);
  };

  loadMore = () => {
    const { paginationInfo } = this.props;
    if (paginationInfo.next_page_url) {
      const page = paginationInfo.current_page + 1;
      const newFilters = { ...this.state.filters, page };
      this.props.fetchArticles(newFilters);
    }
  };

  componentDidUpdate(prevProps) {
    if (prevProps.paginationInfo.total !== this.props.paginationInfo.total) {
      const { filters } = this.state;
      const searchHeading = filters.title
        ? `Results for: ${filters.title}`
        : "";
      const searchTotal = `Results total: ${this.props.paginationInfo.total}`;
      this.setState({ searchHeading, searchTotal });
    }
  }

  render() {
    const { articles, isLoading, paginationInfo, setSelectedArticle } =
      this.props;
    const { searchHeading, searchTotal } = this.state;

    let articleList = articles.length ? (
      articles.map((a) => (
        <ArticleItem key={a.id} {...a} onClick={() => setSelectedArticle(a)} />
      ))
    ) : (
      <div className="container">
        <div className="row justify-content-center">
          <p>No articles found.</p>
        </div>
      </div>
    );

    return (
      <div className="container mt-5">
        <SearchBar fetchQuery={this.applyFilters} searchType="articles" />
        <div className="container mt-5">
          {searchHeading && <h4>{searchHeading}</h4>}
          {searchTotal && <h4>{searchTotal}</h4>}
          {isLoading ? (
            <div className="row justify-content-center">
              <img src={Spinner} alt="Loading..." />
            </div>
          ) : (
            <div className="row">{articleList}</div>
          )}
        </div>
        <div className="row justify-content-center">
          {!isLoading &&
          (paginationInfo.current_page === paginationInfo.last_page ||
            !paginationInfo.next_page_url) ? (
            "No more results..."
          ) : (
            <button
              className="btn btn-outline-primary brand-button col-6"
              onClick={this.loadMore}
            >
              Load More
            </button>
          )}
        </div>
      </div>
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
  setSelectedArticle,
};

export default connect(mapStateToProps, mapDispatchToProps)(ArticleList);
