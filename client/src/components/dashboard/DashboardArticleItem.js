import React from "react";
import Moment from "react-moment";
import { Button, ListGroup } from "react-bootstrap";
import { Link } from "react-router-dom";
import Hashtags from "../ui/hashtags";

const DashboardArticleItem = ({
  id,
  created_at,
  title_jp,
  commentsTotal,
  likesTotal,
  viewsTotal,
  hashtags,
}) => (
  <div className="row">
    <div className="col-md-8 pb-3 mb-0 border-bottom border-gray">
      <p>{title_jp}</p>
      <div className="d-flex align-items-center">
        <span className="mr-2 text-muted">Tags:</span>
        <Hashtags hashtags={hashtags} />
      </div>
    </div>
    <div className="col-md-4">
      <ListGroup variant="flush" className="text-muted">
        <ListGroup.Item className="p-0 d-flex justify-content-between align-items-center">
          <span>{commentsTotal} Comments</span>
          <span>{viewsTotal} Views</span>
          <span>{likesTotal} Likes</span>
          <Link to={`/article/${id}`}>
            <Button variant="outline-primary" size="sm" className="ml-2">
              <i className="fas fa-external-link-alt"></i>
            </Button>
          </Link>
        </ListGroup.Item>
        <ListGroup.Item className="p-0">
          <small>
            <Moment format="Do MMM YYYY">{created_at}</Moment>
          </small>
        </ListGroup.Item>
      </ListGroup>
    </div>
  </div>
);

export default DashboardArticleItem;
