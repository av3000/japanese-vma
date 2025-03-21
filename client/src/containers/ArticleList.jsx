import React, { useState, useEffect } from "react";
import { useSelector, useDispatch } from "react-redux";
import { fetchArticles, setSelectedArticle } from "@store/slices/articlesSlice";
import ArticleItem from "../components/article/ArticleItem";
import Spinner from "../assets/images/spinner.gif";
import SearchBar from "../components/search/Searchbar";

const ArticleList = () => {
  const dispatch = useDispatch();
  const articles = useSelector(state => state.articles.all);
  const isLoading = useSelector(state => state.articles.loading);
  const paginationInfo = useSelector(state => state.articles.paginationInfo);
  
  const [searchState, setSearchState] = useState({
    searchHeading: "",
    searchTotal: "",
    filters: {}
  });
  
  const { searchHeading, searchTotal, filters } = searchState;

  useEffect(() => {
    if (!articles.length) {
      dispatch(fetchArticles());
    }
  }, [dispatch, articles.length]);
  
  useEffect(() => {
    if (paginationInfo.total) {
      const searchHeading = filters.title ? `Results for: ${filters.title}` : "";
      const searchTotal = `Results total: ${paginationInfo.total}`;
      setSearchState(prev => ({ ...prev, searchHeading, searchTotal }));
    }
  }, [paginationInfo.total, filters.title]);

  const applyFilters = (filters) => {
    setSearchState(prev => ({ ...prev, filters }));
    dispatch(fetchArticles(filters));
  };

  const loadMore = () => {
    if (paginationInfo.next_page_url) {
      const page = paginationInfo.current_page + 1;
      const newFilters = { ...filters, page };
      dispatch(fetchArticles(newFilters));
    }
  };

  const handleSetSelectedArticle = (article) => {
    dispatch(setSelectedArticle(article));
  };

  const articleList = articles.length ? (
    articles.map(a => (
      <ArticleItem 
        key={a.id} 
        {...a} 
        onClick={() => handleSetSelectedArticle(a)} 
      />
    ))
  ) : (
    <div className="container">
      <div className="row justify-content-center">
        <p>No articles found.</p>
      </div>
    </div>
  );

  return (
    <div className="container">
      <SearchBar fetchQuery={applyFilters} searchType="articles" />
      {searchHeading && <h4>{searchHeading}</h4>}
      {searchTotal && <h4>{searchTotal}</h4>}
      
      {isLoading ? (
        <div className="row justify-content-center">
          <img src={Spinner} alt="Loading..." />
        </div>
      ) : (
        <div>
          <div>Total Articles: {paginationInfo.total}</div>
          <div className="row">{articleList}</div>
        </div>
      )}
      
      <div className="row justify-content-center">
        {!isLoading &&
        (paginationInfo.current_page === paginationInfo.last_page ||
          !paginationInfo.next_page_url) ? (
          "No more results..."
        ) : (
          <button
            className="btn btn-outline-primary brand-button col-6"
            onClick={loadMore}
          >
            Load More
          </button>
        )}
      </div>
    </div>
  );
};

export default ArticleList;