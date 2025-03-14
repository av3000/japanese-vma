import React from "react";
import Comment from "./Comment";

const CommentList = ({
  comments = [],
  currentUser,
  objectId,
  deleteComment,
  editComment,
  likeComment,
}) => {
  return (
    <div>
      <h5 className="text-muted mb-4 mt-4">
        <span className="badge badge-secondary">{comments.length}</span>
        {"  "}
        Comment{comments.length !== 1 ? "s" : ""}
      </h5>

      {comments.length === 0 ? (
        <div className="alert text-center alert-info">
          Be the first to comment
        </div>
      ) : (
        comments.map((comment, index) => (
          <Comment
            key={index}
            currentUser={currentUser}
            comment={comment}
            objectId={objectId}
            deleteComment={() => deleteComment(comment.id)}
            editComment={() => editComment(comment.id)}
            likeComment={() => likeComment(comment.id)}
          />
        ))
      )}
    </div>
  );
};

export default CommentList;
