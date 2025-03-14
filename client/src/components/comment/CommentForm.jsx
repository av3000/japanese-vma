import React, { useState } from "react";
import axios from "axios";
import { BASE_URL } from "../../shared/constants";

const MAX_CHAR_LIMIT = 1000;

const CommentForm = ({
  currentUser,
  history,
  objectId,
  objectType,
  addComment,
}) => {
  const [message, setMessage] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  const handleChange = (e) => {
    const newMessage = e.target.value.slice(0, MAX_CHAR_LIMIT);
    setMessage(newMessage);
    setError(newMessage.trim() ? "" : "Message is empty.");
  };

  const onSubmit = async (e) => {
    e.preventDefault();

    if (!currentUser.isAuthenticated) {
      history.push("/login");
      return;
    }

    if (!message.trim()) {
      setError("Message is empty.");
      return;
    }

    setIsLoading(true);
    const url = `${BASE_URL}/api/${objectType}/${objectId}/comment`;

    try {
      const res = await axios.post(url, { content: message });
      res.data.comment.userName = currentUser.user.name;
      addComment(res.data.comment);
      setMessage("");
    } catch (err) {
      console.error("Error adding comment:", err);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={onSubmit}>
      <div className="form-group">
        <textarea
          onChange={handleChange}
          value={message}
          className="form-control"
          placeholder="Your Comment"
          name="message"
          rows="5"
          maxLength={MAX_CHAR_LIMIT}
        />
        <small className="form-text text-muted">
          {MAX_CHAR_LIMIT - message.length} characters remaining
        </small>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      <div className="form-group">
        <button
          type="submit"
          disabled={isLoading || !message.trim()}
          className="btn btn-outline-primary col-md-3 brand-button"
        >
          {isLoading ? (
            <span
              className="spinner-border spinner-border-sm"
              role="status"
              aria-hidden="true"
            ></span>
          ) : (
            <span>
              Comment
              <i className="ml-2 fa-regular fa-paper-plane"></i>
            </span>
          )}
        </button>
      </div>
    </form>
  );
};

export default CommentForm;
