import React from "react";
import Moment from "react-moment";
import { Link } from "react-router-dom";

const DashboardListItem = ({
  id,
  created_at,
  title,
  commentsTotal,
  likesTotal,
  viewsTotal,
  hashtags,
  listType,
}) => (
  <div className="row">
    <div className="col-md-8 pb-3 mb-0 border-bottom border-gray">
      <p>{title}</p>
      tags:{" "}
      {hashtags.map((tag) => (
        <span key={tag.id} className="tag-link">
          {tag.content}{" "}
        </span>
      ))}
    </div>
    <div className="col-md-4">
      <Link to={`/list/${id}`}>
        <strong className="d-block text-gray-dark float-right">
          <i className="fas fa-external-link-alt"></i>
        </strong>
      </Link>
      <small className="d-block text-muted">
        <span>{commentsTotal}&nbsp;Comments&nbsp;</span>
        <span>{viewsTotal} &nbsp;Views&nbsp;</span>
        <span>{likesTotal} &nbsp;Likes &nbsp;</span>
      </small>
      <small className="d-block text-muted">
        <span>{listType}</span>
      </small>
      <small className="d-block text-muted">
        <span>
          <Moment className="text-muted" format="Do MMM YYYY">
            {created_at}
          </Moment>
        </span>
      </small>
    </div>
  </div>
);

export default DashboardListItem;
