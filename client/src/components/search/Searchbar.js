import React, { useState } from "react";
import { Form, Button, Row, Col } from "react-bootstrap";

const Searchbar = ({ fetchQuery, searchType }) => {
  const [keyword, setKeyword] = useState("");
  const [sortByWhat, setSortByWhat] = useState("new");
  const [filterType, setFilterType] = useState("20");

  const handleSubmit = (e) => {
    e.preventDefault();
    const data = {
      keyword,
      sortByWhat,
      filterType,
    };
    fetchQuery(data);
  };

  return (
    <Form onSubmit={handleSubmit} className="container">
      <Row>
        <Col lg={4} md={6} sm={12} className="mt-3">
          <Form.Control
            type="text"
            placeholder="Ex.: title, text, #tag"
            aria-label="Search"
            name="keyword"
            value={keyword}
            onChange={(e) => setKeyword(e.target.value)}
          />
        </Col>
        <Col lg={4} md={4} sm={12} className="mt-3">
          {searchType === "posts" && (
            <Form.Control
              as="select"
              name="filterType"
              value={filterType}
              onChange={(e) => setFilterType(e.target.value)}
            >
              <option value="20">All</option>
              <option value="1">Content-related</option>
              <option value="2">Off-topic</option>
              <option value="3">FAQ</option>
              <option value="4">Technical</option>
              <option value="5">Bug</option>
              <option value="6">Feedback</option>
              <option value="7">Announcement</option>
            </Form.Control>
          )}
          {searchType === "articles" && (
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
          )}
          {searchType === "lists" && (
            <Form.Control
              as="select"
              name="filterType"
              value={filterType}
              onChange={(e) => setFilterType(e.target.value)}
            >
              <option value="20">All</option>
              <option value="5">Radicals</option>
              <option value="6">Kanjis</option>
              <option value="7">Words</option>
              <option value="8">Sentences</option>
              <option value="9">Articles</option>
            </Form.Control>
          )}
        </Col>
        <Col lg={2} md={2} sm={4} className="mt-3">
          <Form.Control
            as="select"
            name="sortByWhat"
            value={sortByWhat}
            onChange={(e) => setSortByWhat(e.target.value)}
          >
            <option value="new">Newest</option>
            <option value="pop">Popular</option>
          </Form.Control>
        </Col>
        <Col lg={2} className="mt-3">
          <Button
            type="submit"
            variant="outline-primary"
            className="brand-button"
            aria-hidden="true"
          >
            <i className="fas fa-search"></i>
            <span className="ml-2">Search</span>
          </Button>
        </Col>
      </Row>
    </Form>
  );
};

export default Searchbar;
