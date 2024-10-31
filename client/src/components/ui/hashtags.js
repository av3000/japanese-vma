import React from "react";
import { ListGroup, Badge } from "react-bootstrap";

const Hashtags = ({ hashtags }) => {
  return hashtags && hashtags.length > 0 ? (
    <ListGroup horizontal className="flex-wrap">
      {hashtags.map((tag) => (
        <ListGroup.Item key={tag.id} className="p-1 border-0">
          <Badge pill variant="secondary">
            {tag.content}
          </Badge>
        </ListGroup.Item>
      ))}
    </ListGroup>
  ) : (
    "No tags"
  );
};

export default Hashtags;
