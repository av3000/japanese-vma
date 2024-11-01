import React, { Component } from "react";
import { Link } from "react-router-dom";
import { apiCall } from "../services/api";
import ExploreListItem from "../components/list/ExploreListItem";
import Spinner from "../assets/images/spinner.gif";
import { HTTP_METHOD } from "../shared/constants";

class ExploreCustomList extends Component {
  _isMounted = false;
  constructor(props) {
    super(props);
    this.state = {
      lists: [],
      totalLists: null,
      isLoading: false,
    };
  }

  componentDidMount() {
    this._isMounted = true;
    this.fetchLists();
  }

  componentWillUnmount() {
    this._isMounted = false;
  }

  fetchLists() {
    this.setState({ isLoading: true });
    return apiCall(HTTP_METHOD.GET, "/api/lists")
      .then((res) => {
        if (this._isMounted) {
          this.setState({
            totalLists: res.lists.total,
            lists: [...res.lists.data],
            isLoading: false,
          });
        }
      })
      .catch((err) => {
        this.setState({ isLoading: false });
        console.log(err);
      });
  }

  render() {
    const { isLoading, lists } = this.state;
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

    if (isLoading) {
      return (
        <div className="d-flex justify-content-center w-100">
          <img src={Spinner} alt="spinner loading" />
        </div>
      );
    }

    const customLists = lists
      .slice(0, 3)
      .map((l) => (
        <ExploreListItem
          key={l.id}
          id={l.id}
          type={l.type}
          listType={listTypes[l.type - 1]}
          created_at={l.created_at}
          title={l.title}
          commentsTotal={l.commentsTotal}
          likesTotal={l.likesTotal}
          viewsTotal={l.viewsTotal}
          downloadsTotal={l.downloadsTotal}
          hashtags={l.hashtags.slice(0, 3)}
          itemsTotal={l.listItems.length}
          n1={l.n1}
          n2={l.n2}
          n3={l.n3}
          n4={l.n4}
          n5={l.n5}
        />
      ));

    return (
      <React.Fragment>
        <div className="d-flex justify-content-between align-items-center w-100 my-3">
          <h3>Latest Lists ({this.state.totalLists || 0})</h3>
          <div>
            <Link to="/lists" className="homepage-section-title">
              Read All Lists
            </Link>
          </div>
        </div>
        <div className="row">{customLists}</div>
      </React.Fragment>
    );
  }
}

export default ExploreCustomList;
