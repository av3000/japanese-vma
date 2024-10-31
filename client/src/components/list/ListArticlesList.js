import React, { Component } from "react";
import { Link } from "react-router-dom";
import Hashtags from "../ui/hashtags";

class ListArticlesList extends Component {
  render() {
    let { objects, removeFromList, currentUser, listUserId } = this.props;
    const objectList = objects.map((object) => {
      object.hashtags = object.hashtags.slice(0, 3);
      return (
        <tr key={object.id}>
          <th scope="row">
            {
              <Link to={`/article/${object.id}`} target="_blank">
                <i className="fas fa-external-link-alt"></i>
              </Link>
            }
            {currentUser.user.id === listUserId ? (
              <button
                className="btn btn-sm btn-danger"
                onClick={removeFromList.bind(this, object.id)}
              >
                -
              </button>
            ) : (
              ""
            )}
          </th>
          <td>{object.viewsTotal}</td>
          <td>{object.savesTotal}</td>
          <td>{object.downloadsTotal}</td>
          <td>{object.commentsTotal}</td>
          <td>{object.likesTotal}</td>
          <td>
            <Hashtags hashtags={object.hashtags} />
          </td>
        </tr>
      );
    });

    return (
      <table className="table table-responsive-md table-bordered table-hover">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Views</th>
            <th scope="col">Saves</th>
            <th scope="col">Downloads</th>
            <th scope="col">Comments</th>
            <th scope="col">Likes</th>
            <th scope="col">Tags</th>
          </tr>
        </thead>
        <tbody>{objectList}</tbody>
      </table>
    );
  }
}

export default ListArticlesList;
