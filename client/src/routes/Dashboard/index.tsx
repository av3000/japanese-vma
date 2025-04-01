//@ts-nocheck

import React, { useState, useEffect } from "react";

import { useSelector } from "react-redux";

import { apiCall } from "@/services/api";
import DashboardArticleItem from "@/components/dashboard/DashboardArticleItem";
import DashboardListItem from "@/components/dashboard/DashboardListItem";
import Spinner from "@/assets/images/spinner.gif";
import SearchBarDashboard from "@/components/search/SearchBarDashboard";
import { HTTP_METHOD } from "@/shared/constants";
import Hashtags from "@/components/ui/hashtags";
import { Button } from "@/components/shared/Button";
import { Icon } from "@/components/shared/Icon";
import { Link } from "@/components/shared/Link";

const RESOURCE_TYPES = {
  ARTICLES: "ARTICLES",
  LISTS: "LISTS",
};

const DASHBOARD_TYPES = {
  ADMIN: "ADMIN",
  COMMON_USER: "COMMON_USER",
};

const DashboardList: React.FC = () => {
  const currentUser = useSelector((state) => state.currentUser);
  const [currentResource, setCurrentResource] = useState(RESOURCE_TYPES.LISTS);
  const [lists, setLists] = useState([]);
  const [articles, setArticles] = useState([]);
  const [articlesPending, setArticlesPending] = useState([]);
  const [dashboard, setDashboard] = useState(DASHBOARD_TYPES.COMMON_USER);
  const [setFilters] = useState({});
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const { isAuthenticated, user } = currentUser;
    if (isAuthenticated) {
      fetchArticles();
      fetchLists();

      if (user.isAdmin) {
        fetchArticlesPending();
      }
    }
  }, [currentUser]);

  const toggleResource = () => {
    setCurrentResource((prev) =>
      prev === RESOURCE_TYPES.LISTS
        ? RESOURCE_TYPES.ARTICLES
        : RESOURCE_TYPES.LISTS
    );
  };

  const toggleDashboard = () => {
    setDashboard((prev) =>
      prev === DASHBOARD_TYPES.COMMON_USER
        ? DASHBOARD_TYPES.ADMIN
        : DASHBOARD_TYPES.COMMON_USER
    );
    if (dashboard === DASHBOARD_TYPES.ADMIN) {
      fetchArticlesPending();
    }
  };

  const fetchArticlesPending = async () => {
    try {
      setIsLoading(true);
      const res = await apiCall(HTTP_METHOD.GET, "/api/articles/pendinglist");
      setArticlesPending(res.articlesPending);
      setIsLoading(false);
    } catch (err) {
      console.error(err);
      setIsLoading(false);
    }
  };

  const fetchArticles = async () => {
    try {
      setIsLoading(true);
      const res = await apiCall(HTTP_METHOD.GET, "/api/user/articles");
      setArticles(res.articles);
      setIsLoading(false);
    } catch (err) {
      console.error(err);
      setIsLoading(false);
    }
  };

  const fetchLists = async () => {
    try {
      setIsLoading(true);
      const res = await apiCall(HTTP_METHOD.GET, "/api/user/lists");
      setLists(res.lists);
      setIsLoading(false);
    } catch (err) {
      console.error(err);
      setIsLoading(false);
    }
  };

  const loadingSpinner = () => (
    <div className="container mt-5">
      <div className="row justify-content-center">
        <img src={Spinner} alt="Loading..." />
      </div>
    </div>
  );

  const mainContent = isLoading ? (
    loadingSpinner()
  ) : currentResource === RESOURCE_TYPES.LISTS ? (
    <div className="my-3 p-3 bg-white rounded box-shadow">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h4 className="border-bottom border-gray pb-2 mb-0">My Lists</h4>
        {/* <button className="btn btn-sm btn-light" onClick={toggleDashboard}>
          {dashboard === DASHBOARD_TYPES.ADMIN ? "User" : "Admin"}{" "}
          <i className="fas fa-arrow-right"></i>
        </button> */}
      </div>
      <div className="col-lg-12 col-md-10 mx-auto">
        {lists.length > 0 ? (
          lists.map((list) => (
            <DashboardListItem
              key={list.id}
              {...list}
              currentUser={currentUser}
            />
          ))
        ) : (
          <div className="alert text-center alert-info">
            You have no Lists yet.
          </div>
        )}
      </div>
    </div>
  ) : (
    <div className="my-3 p-3 bg-white rounded box-shadow">
      {dashboard === DASHBOARD_TYPES.ADMIN ? (
        <>
          <div className="d-flex justify-content-between align-items-center mb-3">
            <h4>Pending Articles - Admin view</h4>
            <Button variant="ghost" onClick={toggleDashboard}>
              User View <Icon name="chevron" rotate="270" />
            </Button>
          </div>
          <div className="col-lg-12 col-md-12 mx-auto">
            {articlesPending.length ? (
              articlesPending.map((article) => (
                <div
                  className="row pb-3 mb-0 mt-3 border-bottom border-gray"
                  key={article.id}
                >
                  <div className="col-lg-6">
                    <h4>
                      <Link to={`/article/${article.id}`}>
                        {article.title_jp}
                      </Link>
                    </h4>
                    tags: <Hashtags hashtags={article.hashtags} />
                  </div>
                  <div className="col-lg-4 col-12-sm pt-3">
                    <small className="text-muted">
                      {article.created_at}
                      <br />
                      duration from now(?) {article.created_at}
                    </small>
                  </div>
                  <div className="col-lg-2">
                    <strong>{article.statusTitle}</strong>
                  </div>
                </div>
              ))
            ) : (
              <div className="alert text-center alert-info">
                There are no articles to review.
              </div>
            )}
          </div>
        </>
      ) : (
        <>
          <div className="d-flex justify-content-between align-items-center mb-3">
            <h4>My Articles - User view</h4>
            <Button variant="ghost" onClick={toggleDashboard}>
              Admin View <Icon name="chevron" rotate="270" />
            </Button>
          </div>
          <div className="col-lg-12 col-md-10 mx-auto">
            {articles.map((article) => (
              <DashboardArticleItem
                key={article.id}
                {...article}
                currentUser={currentUser}
              />
            ))}
            {articles.length === 0 && <div>No articles yet.</div>}
          </div>
        </>
      )}
    </div>
  );

  return (
    <div className="container mt-5">
      <div className="container mt-5">
        <div className="ml-3 mt-2">
          <div className="row align-items-center">
            <div className="col-auto">
              <Button variant="ghost" onClick={toggleResource}>
                {currentResource === RESOURCE_TYPES.LISTS
                  ? "Articles"
                  : "Lists"}{" "}
                <Icon name="chevron" rotate="270" />
              </Button>
            </div>

            <div className="col">
              <SearchBarDashboard
                searchType={
                  currentResource === RESOURCE_TYPES.LISTS
                    ? "lists"
                    : "articles"
                }
                filterResults={(newFilters) => {
                  setFilters(newFilters);
                }}
              />
            </div>
          </div>
        </div>
        {mainContent}
      </div>
    </div>
  );
};

export default DashboardList;
