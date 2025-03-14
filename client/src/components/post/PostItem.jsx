import React from "react";
import { Link } from "react-router-dom";

import Hashtags from "../ui/hashtags";

const PostItem = ({
  id,
  title,
  type,
  userId,
  commentsTotal,
  likesTotal,
  viewsTotal,
  hashtags,
  userName,
  postType,
  date,
  isLocked,
}) => (
  <div className="row border-bottom border-gray">
    <div className="col-lg-10 col-md-12 col-12-sm pb-3 pt-3">
      <p>
        <strong className="d-block text-gray-dark">{userName}</strong>
      </p>
      <h5>
        <Link to={`/community/${id}`}>{title}</Link>
      </h5>
      Date:{" "}
      {date}
      {/* <Moment className="text-muted" format="Do MMM YYYY">
        moment(date).format()
      </Moment> */}
      <br />
      Tags: <Hashtags hashtags={hashtags} />
    </div>
    <div className="col-lg-2 col-12-sm pt-3">
      <small>
        <span>
          <strong className="d-block text-gray-dark">{postType}</strong>
        </span>
        <span>{commentsTotal}&nbsp;Comments</span> <br />
        <span>{viewsTotal}&nbsp;Views</span> <br />
        <span>{likesTotal}&nbsp;Likes &nbsp;</span> <br />
        <span>{isLocked ? <strong>Locked </strong> : ""}</span>
      </small>
    </div>
  </div>
);

export default PostItem;
