import React from "react";
import { Badge } from "react-bootstrap";

const ArticleStatusTypes = {
  PENDING: 0,
  REVIEWING: 1,
  REJECTED: 2,
  APPROVED: 3,
};

const ArticleStatus = ({ status }) => {
  switch (status) {
    case ArticleStatusTypes.PENDING:
      return <Badge variant="warning">Pending</Badge>;
    case ArticleStatusTypes.REVIEWING:
      return <Badge variant="warning">Reviewing</Badge>;
    case ArticleStatusTypes.REJECTED:
      return <Badge variant="danger">Rejected</Badge>;
    case ArticleStatusTypes.APPROVED:
      return <Badge variant="success">Approved</Badge>;
    default:
      return <Badge variant="warning">Pending</Badge>;
  }
};

export default ArticleStatus;
