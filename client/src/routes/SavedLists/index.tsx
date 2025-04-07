// @ts-nocheck
import React, { Component } from "react";
import { apiCall } from "@/services/api";
import Spinner from "@/assets/images/spinner.gif";
import SearchBar from "@/components/SearchBar";
import { Button } from "@/components/shared/Button";
import { SavedListItem } from "@/components/features/SavedList/SavedListItem";

class SavedLists extends Component {
  constructor() {
    super();
    this.state = {
      url: "/api/lists",
      pagination: [],
      lists: [],
      paginateObject: {},
      searchHeading: "",
      searchTotal: "",
      filters: [],
      isLoading: true,
    };

    this.loadMore = this.loadMore.bind(this);
    this.loadSearchMore = this.loadSearchMore.bind(this);
    this.fetchQuery = this.fetchQuery.bind(this);
    this.fetchMoreQuery = this.fetchMoreQuery.bind(this);
  }

  componentDidMount() {
    this.fetchLists(this.state.url);
  }

  fetchQuery(queryParams) {
    this.setState({ isLoading: true });
    let newState = Object.assign({}, this.state);
    newState.filters = queryParams;
    apiCall("post", "/api/lists/search", newState.filters)
      .then((res) => {
        if (res.success === true) {
          newState.paginateObject = res.lists;
          newState.lists = res.lists.data ? res.lists.data : newState.lists;
          newState.url = res.lists.next_page_url;

          newState.searchHeading = res.requestedQuery;
          newState.searchTotal = "results total: " + res.lists.total;
          return newState;
        }
      })
      .then((newState) => {
        newState.pagination = this.makePagination(newState.paginateObject);
        newState.isLoading = false;
        this.setState(newState);
      })
      .catch((err) => {
        newState.isLoading = false;
        this.setState(newState);
        console.log(err);
      });
  }

  fetchLists(givenUrl) {
    this.setState({ isLoading: true });
    return apiCall("get", givenUrl)
      .then((res) => {
        let newState = Object.assign({}, this.state);
        newState.paginateObject = res.lists;
        newState.lists = [...newState.lists, ...res.lists.data];
        newState.url = res.lists.next_page_url;

        newState.searchTotal = "results total: " + res.lists.total;

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

  fetchMoreQuery(givenUrl) {
    this.setState({ isLoading: true });
    let newState = Object.assign({}, this.state);
    apiCall("post", givenUrl, newState.filters)
      .then((res) => {
        newState.paginateObject = res.lists;
        newState.lists = [...newState.lists, ...res.lists.data];
        newState.url = res.lists.next_page_url;

        newState.searchTotal = "results total: " + res.lists.total;

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
    this.fetchLists(this.state.pagination.next_page_url);
  }

  loadSearchMore() {
    this.fetchMoreQuery(this.state.pagination.next_page_url);
  }

  makePagination(data) {
    let pagination = {
      current_page: data.current_page,
      last_page: data.last_page,
      next_page_url: data.next_page_url,
      prev_page_url: data.prev_page_url,
    };

    return pagination;
  }

  render() {
    const listTypes = [
      "knownradicals list",
      "knownkanjis list",
      "knownwords list",
      "knownsentences list",
      "Radicals List",
      "Kanjis List",
      "Words List",
      "Sentences List",
      "Articles List",
    ];

    let { lists } = this.state;
    let customLists = lists ? (
      lists.map((l) => (
        <SavedListItem
          key={l.id}
          id={l.id}
          listType={listTypes[l.type - 1]}
          itemsTotal={l.listItems.length}
          hashtags={l.hashtags.slice(0, 3)}
          {...l}
        />
      ))
    ) : (
      <div className="container">
        <div className="row justify-content-center">
          <img src={Spinner} alt="spinner" />
        </div>
      </div>
    );

    return (
      <div className="container">
        <SearchBar fetchQuery={this.fetchQuery} searchType="lists" />
        {this.state.searchHeading && <h4>{this.state.searchHeading}</h4>}
        {this.state.searchTotal && <h4>{this.state.searchTotal}</h4>}
        <div className="row">{customLists}</div>
        <div className="row justify-content-center">
          {this.state.isLoading ? (
            <div className="container">
              <div className="row justify-content-center">
                <img src={Spinner} alt="spinner" />
              </div>
            </div>
          ) : (
            this.state.pagination.last_page !==
              this.state.pagination.current_page && (
              <Button
                variant="outline"
                size="md"
                type="button"
                onClick={
                  this.state.url.includes("search")
                    ? this.loadSearchMore
                    : this.loadMore
                }
              >
                Load More
              </Button>
            )
          )}
        </div>
      </div>
    );
  }
}

export default SavedLists;
