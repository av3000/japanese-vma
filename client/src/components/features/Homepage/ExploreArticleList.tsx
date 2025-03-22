// @ts-nocheck

import React, { useEffect } from "react";
import { useSelector, useDispatch } from "react-redux";
import { Link } from "react-router-dom";
import { fetchArticles } from "@store/slices/articlesSlice";
import ExploreArticleItem from "./ExploreArticleItem";
import Spinner from "@/assets/images/spinner.gif";
import Button from "@/components/shared/Button";

const ExploreArticleList: React.FC = () => {
  const dispatch = useDispatch();
  const articles = useSelector((state) => state.articles.all);
  const isLoading = useSelector((state) => state.articles.loading);
  const paginationInfo = useSelector((state) => state.articles.paginationInfo);

  useEffect(() => {
    if (!articles.length) {
      dispatch(fetchArticles());
    }
  }, [dispatch, articles.length]);

  if (isLoading) {
    return (
      <div className="d-flex justify-content-center w-100">
        <img src={Spinner} alt="spinner loading" />
      </div>
    );
  }

  const featuredArticles = articles
    .slice(0, 3)
    .map((a) => <ExploreArticleItem key={a.id} {...a} />);

  return (
    <>
      <div className="d-flex justify-content-between align-items-center w-100 my-3">
        <Button>New Button</Button>
        <h3>Latest Articles ({paginationInfo.total || 0})</h3>
        <div>
          <Link to="/articles" className="homepage-section-title">
            Read All Articles
          </Link>
        </div>
      </div>
      <div className="row">{featuredArticles}</div>
    </>
  );
};

export default ExploreArticleList;
