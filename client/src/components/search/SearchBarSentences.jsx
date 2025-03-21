import React, { useState } from "react";
import { Form, Button, InputGroup } from "react-bootstrap";

const SearchBarSentences = ({ fetchQuery }) => {
  const [keyword, setKeyword] = useState("");

  const handleSubmit = (e) => {
    e.preventDefault();
    fetchQuery({ keyword });
  };

  return (
    <Form onSubmit={handleSubmit} className="col-lg-12">
      <div className="row justify-content-center">
        <div lg={8} md={10} className="col mx-auto">
          <Form.Group controlId="formKeyword">
            <Form.Label>Japanese Keyword:</Form.Label>
            <InputGroup size="sm">
              <Form.Control
                type="text"
                placeholder="Search"
                aria-label="Search"
                name="keyword"
                value={keyword}
                onChange={(e) => setKeyword(e.target.value)}
              />
              <InputGroup.Append>
                <Button
                  variant="outline-primary"
                  type="submit"
                  className="brand-button"
                >
                  <i className="fas fa-search"></i>
                  <span className="ml-2">Search</span>
                </Button>
              </InputGroup.Append>
            </InputGroup>
          </Form.Group>
        </div>
      </div>
    </Form>
  );
};

export default SearchBarSentences;
