// @ts-nocheck
import React, { Component } from "react";
import { apiCall } from "@/services/api";
import KanjiItem from "@/components/kanji/KanjiItem";
import Spinner from "@/assets/images/spinner.gif";
import SearchBarKanjis from "@/components/search/SearchBarKanjis";
import { HTTP_METHOD } from "@/shared/constants";

export class KanjiList extends Component {
  constructor() {
    super();
    this.state = {
      url: `${process.env.REACT_APP_API_HOS}/api/kanjis`,
      pagination: [],
      kanjis: [],
      paginateObject: {},
      searchHeading: "",
      searchTotal: "",
      filters: [],
      isLoading: false,
    };

    this.loadMore = this.loadMore.bind(this);
    this.loadSearchMore = this.loadSearchMore.bind(this);
    this.fetchQuery = this.fetchQuery.bind(this);
    this.fetchMoreQuery = this.fetchMoreQuery.bind(this);
  }

  componentDidMount() {
    this.fetchKanjis(this.state.url);
  }

  fetchKanjis() {
    this.setState({ isLoading: true });
    return apiCall(HTTP_METHOD.GET, "/api/kanjis")
      .then((res) => {
        let newState = Object.assign({}, this.state);
        newState.paginateObject = res.kanjis;
        newState.kanjis = [...newState.kanjis, ...res.kanjis.data];
        newState.url = res.kanjis.next_page_url;

        newState.searchTotal = "results total: '" + res.kanjis.total + "'";

        return newState;
      })
      .then((newState) => {
        newState.pagination = this.makePagination(newState.paginateObject);
        newState.isLoading = false;
        this.setState(newState);
      })
      .catch((err) => {
        console.log(err);
        this.setState({ isLoading: false });
      });
  }

  fetchQuery(queryParams) {
    this.setState({ isLoading: true });
    let newState = Object.assign({}, this.state);
    newState.filters = queryParams;
    apiCall("post", "/api/kanjis/search", newState.filters)
      .then((res) => {
        if (res.success === true) {
          newState.paginateObject = res.kanjis;
          newState.kanjis = res.kanjis.data ? res.kanjis.data : newState.kanjis;
          newState.url = res.kanjis.next_page_url;

          newState.searchHeading = res.requestedQuery;
          newState.searchTotal = "Results total: '" + res.kanjis.total + "'";
          return newState;
        }
      })
      .then((newState) => {
        newState.pagination = this.makePagination(newState.paginateObject);
        newState.isLoading = false;

        this.setState(newState);
      })
      .catch((err) => {
        this.setState(newState);
        this.setState({ isLoading: false });
        console.log(err);
      });
  }

  fetchMoreQuery() {
    let newState = Object.assign({}, this.state);
    this.setState({ isLoading: true });
    apiCall(HTTP_METHOD.POST, "/api/kanjis", newState.filters)
      .then((res) => {
        newState.paginateObject = res.kanjis;
        newState.kanjis = [...newState.kanjis, ...res.kanjis.data];
        newState.url = res.kanjis.next_page_url;

        newState.searchTotal = "Results total: '" + res.kanjis.total + "'";

        return newState;
      })
      .then((newState) => {
        newState.pagination = this.makePagination(newState.paginateObject);
        newState.isLoading = false;

        this.setState(newState);
      })
      .catch((err) => {
        this.setState({ isLoading: false });
        console.log(err);
      });
  }

  loadMore() {
    this.fetchKanjis(this.state.pagination.next_page_url);
  }

  loadSearchMore() {
    this.fetchMoreQuery(this.state.pagination.next_page_url);
  }

  makePagination(data) {
    return {
      current_page: data.current_page,
      last_page: data.last_page,
      next_page_url: data.next_page_url,
      prev_page_url: data.prev_page_url,
    };
  }

  addToList(id) {
    console.log("addtoList kanjiList: " + id);
  }

  render() {
    const { kanjis, isLoading } = this.state;

    if (isLoading) {
      return (
        <div className="container text-center">
          <img src={Spinner} alt="Loading..." />
        </div>
      );
    }

    const kanjiList = kanjis.map((k) => {
      k.meaning = k.meaning.split("|");
      k.meaning = k.meaning.slice(0, 3);
      k.meaning = k.meaning.join(", ");

      k.onyomi = k.onyomi.split("|");
      k.onyomi = k.onyomi.slice(0, 3);
      k.onyomi = k.onyomi.join(", ");

      k.kunyomi = k.kunyomi.split("|");
      k.kunyomi = k.kunyomi.slice(0, 3);
      k.kunyomi = k.kunyomi.join(", ");

      return (
        <KanjiItem
          key={k.id}
          id={k.id}
          {...k}
          parts={k.radical_parts}
          addToList={this.addToList.bind(this, k.id)}
        />
      );
    });

    return (
      <div className="container mt-5">
        <div className="row justify-content-center">
          <SearchBarKanjis fetchQuery={this.fetchQuery} />
        </div>
        <div className="container mt-5">
          <div className="row justify-content-center">
            {this.state.searchHeading ? (
              <h4>{this.state.searchHeading}</h4>
            ) : (
              ""
            )}
            &nbsp;
            {this.state.searchTotal ? <h4>{this.state.searchTotal}</h4> : ""}
          </div>
          <div className="row">
            <div className="col-lg-8 col-md-10 mx-auto">{kanjiList}</div>
          </div>
        </div>
        <div className="row justify-content-center">
          {isLoading ? (
            <div className="container mt-5">
              <div className="row justify-content-center">
                <img src={Spinner} alt="spinner" />
              </div>
            </div>
          ) : (
            ""
          )}
          {!isLoading &&
          this.state.pagination.last_page ===
            this.state.pagination.current_page ? (
            "no more results..."
          ) : this.state.url.includes("search") ? (
            <button
              className="btn btn-outline-primary brand-button col-6"
              onClick={this.loadSearchMore}
            >
              Load More
            </button>
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

export default KanjiList;
