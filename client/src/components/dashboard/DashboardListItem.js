import React from "react";
import Moment from "react-moment";
import { Link } from "react-router-dom";
import { Button, ListGroup, Badge } from "react-bootstrap";

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
        <ListGroup horizontal className="flex-wrap">
          {hashtags.map((tag) => (
            <ListGroup.Item key={tag.id} className="p-2 border-0">
              <Badge pill variant="secondary">
                {tag.content}
              </Badge>
            </ListGroup.Item>
          ))}
        </ListGroup>
      </div>
    </div>
    <div className="col-md-4">
      <ListGroup variant="flush" className="text-muted">
        <ListGroup.Item className="p-0 d-flex justify-content-between align-items-center">
          <span>{commentsTotal} Comments</span>
          <span>{viewsTotal} Views</span>
          <span>{likesTotal} Likes</span>
          <Link to={`/list/${id}`}>
            <Button variant="outline-primary" size="sm" className="m-2">
              <i className="fas fa-external-link-alt"></i>
            </Button>
          </Link>
        </ListGroup.Item>
        <small>ListType: {typeTitle}</small>
        <ListGroup.Item className="p-0">
          <small>
            <Moment format="Do MMM YYYY">{created_at}</Moment>
          </small>
        </ListGroup.Item>
      </ListGroup>
    </div>
  </div>
);

export default DashboardListItem;
