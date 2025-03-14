import React, { useState } from "react";
import { Form, Button, InputGroup } from "react-bootstrap";

const SearchBarKanjis = ({ fetchQuery }) => {
  const [keyword, setKeyword] = useState("");
  const [filterType, setFilterType] = useState("20");

  const handleSubmit = (e) => {
    e.preventDefault();
    fetchQuery({ keyword, filterType });
  };

  return (
    <Form onSubmit={handleSubmit} className="col-lg-12">
      <div className="row justify-content-center">
        <div lg={4} md={5} sm={12} className="col mb-2">
          <Form.Label>Keyword:</Form.Label>
          <InputGroup size="sm">
            <Form.Control
              type="text"
              placeholder="Search"
              aria-label="Search"
              name="keyword"
              value={keyword}
              onChange={(e) => setKeyword(e.target.value)}
            />
          </InputGroup>
        </div>
        <div lg={3} md={4} sm={12} className="col mb-2">
          <Form.Label>JLPT:</Form.Label>
          <InputGroup size="sm">
            <Form.Control
              as="select"
              name="filterType"
              value={filterType}
              onChange={(e) => setFilterType(e.target.value)}
            >
              <option value="20">All</option>
              <option value="1">N1</option>
              <option value="2">N2</option>
              <option value="3">N3</option>
              <option value="4">N4</option>
              <option value="5">N5</option>
              <option value="6">Uncommon</option>
            </Form.Control>
          </InputGroup>
        </div>
      </div>
      <div className="row justify-content-center" lg={2} md={2} sm={12}>
        <div lg={2} md={3} sm={4} className="text-center">
          <Button
            type="submit"
            variant="outline-primary"
            className="brand-button mt-3"
          >
            <i className="fas fa-search"></i>
            <span className="ml-2">Search</span>
          </Button>
        </div>
      </div>
    </Form>
  );
};

export default SearchBarKanjis;
