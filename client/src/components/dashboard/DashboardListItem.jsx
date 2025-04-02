import React from "react";

import { Link } from "react-router-dom";
import { Button, ListGroup } from "react-bootstrap";
import Hashtags from "../ui/hashtags";
import { Button } from "../shared/Button";

const DashboardListItem = ({
  id,
  created_at,
  title,
  commentsTotal,
  likesTotal,
  viewsTotal,
  hashtags,
  typeTitle,
}) => (
  <div className="row border-bottom border-gray">
    <div className="col-md-8 ">
      <p className="text-muted">{title}</p>
      <div className="d-flex align-items-center mt-3">
        <span className="text-muted">Tags:</span>
        <Hashtags hashtags={hashtags} />
      </div>
    </div>
    <div className="col-md-4">
      <ListGroup variant="flush" className="text-muted">
        <ListGroup.Item className="p-0 d-flex justify-content-between align-items-center">
          <span>{commentsTotal} Comments</span>
          <span>{viewsTotal} Views</span>
          <span>{likesTotal} Likes</span>
          <Button to={`/list/${id}`} variant="outline" size="sm" type="button">
            <Icon name="externalLink" size="sm" />
          </Button>
        </ListGroup.Item>
        <small>ListType: {typeTitle}</small>
        <ListGroup.Item className="p-0">
          <small>{created_at}</small>
        </ListGroup.Item>
      </ListGroup>
    </div>
  </div>
);

export default DashboardListItem;
