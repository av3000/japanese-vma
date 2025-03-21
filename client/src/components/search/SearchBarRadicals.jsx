import React, { useState } from "react";
import { Form, Button, InputGroup } from "react-bootstrap";

const SearchBarRadicals = ({ fetchQuery }) => {
  const [keyword, setKeyword] = useState("");

  const handleSubmit = (e) => {
    e.preventDefault();
    fetchQuery({ keyword });
  };

  return (
    <Form onSubmit={handleSubmit} className="col-lg-12">
      <div className="justify-content-center">
        <div lg={8} md={10} className="mx-auto">
          <Form.Label>Keyword:</Form.Label>
          <InputGroup size="sm">
            <Form.Control
              type="text"
              placeholder="Search"
              aria-label="Search"
              name="keyword"
              value={keyword}
              onChange={(e) => setKeyword(e.target.value)}
              className="form-control form-control-sm"
            />
            <InputGroup.Append>
              <Button
                className="btn btn-outline-primary brand-button"
                aria-hidden="true"
              >
                <i className="fas fa-search"></i>
                <span className="ml-2">Search</span>
              </Button>
            </InputGroup.Append>
          </InputGroup>
        </div>
      </div>
    </Form>
  );
};

export default SearchBarRadicals;
