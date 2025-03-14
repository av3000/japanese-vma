import React from "react";

import { Button } from "react-bootstrap";
import DefaultAvatar from "../../assets/images/avatar-man.svg";

const Comment = ({ comment, deleteComment, likeComment, currentUser }) => (
  <div className="media">
    <img
      className="d-flex mr-3 rounder-circle"
      src={DefaultAvatar}
      alt="default-avatar"
    />
    <div className="media-body">
      <div className="d-flex justify-content-between align-items-center">
        <h5>@{comment.userName}</h5>
        {currentUser.user.id === comment.user_id || currentUser.user.isAdmin ? (
          <Button
            onClick={deleteComment}
            variant="outline-primary"
            className="btn btn-outline brand-button"
            size="sm"
          >
            <i className="far fa-trash-alt"></i>
          </Button>
        ) : null}
      </div>

      <div>{comment.content}</div>
      <br />

      <div className="text-muted d-flex align-items-center">
        <span className="mx-2">{comment.likesTotal} likes</span>
        <Button
          onClick={likeComment}
          variant="outline-primary"
          className={
            comment.isLiked
              ? "btn btn-outline brand-button liked-button"
              : "btn btn-outline brand-button"
          }
          size="sm"
        >
          <i
            className={
              comment.isLiked ? "fas fa-thumbs-up" : "far fa-thumbs-up"
            }
          ></i>
        </Button>
        <p className="ml-auto mb-0">
          {/* <Moment className="text-muted" format="Do MMM YYYY">
            {moment(comment.created_at).format()}
          </Moment> */}
        </p>
      </div>
      <hr />
    </div>
  </div>
);

export default Comment;
