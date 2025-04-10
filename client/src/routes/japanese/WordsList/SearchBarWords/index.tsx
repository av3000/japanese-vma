import { Button } from "@/components/shared/Button";
import { Icon } from "@/components/shared/Icon";
import React, { useState, FormEvent } from "react";
import { Form, InputGroup } from "react-bootstrap";

interface SearchQuery {
  keyword: string;
  filterType: string | number;
}

interface SearchBarWordsProps {
  fetchQuery: (query: SearchQuery) => void;
}

const SearchBarWords: React.FC<SearchBarWordsProps> = ({ fetchQuery }) => {
  const [keyword, setKeyword] = useState<string>("");
  const [filterType, setFilterType] = useState<string | number>(20);

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    fetchQuery({ keyword, filterType });
  };

  const handleKeywordChange = (e: React.ChangeEvent<HTMLElement>) => {
    const target = e.target as HTMLInputElement;
    setKeyword(target.value);
  };

  const handleFilterChange = (e: React.ChangeEvent<HTMLElement>) => {
    const target = e.target as HTMLSelectElement;
    setFilterType(target.value);
  };

  return (
    <Form onSubmit={handleSubmit} className="col-lg-12">
      <div className="justify-content-center">
        <div className="col-lg-4 col-md-5 col-sm-12 mb-2">
          <Form.Label>Japanese Keyword:</Form.Label>
          <InputGroup size="sm">
            <Form.Control
              type="text"
              placeholder="Search"
              aria-label="Search"
              name="keyword"
              value={keyword}
              onChange={handleKeywordChange}
            />
          </InputGroup>
        </div>
        <div className="col-lg-3 col-md-4 col-sm-12 mb-2">
          <Form.Label>Word Type:</Form.Label>
          <InputGroup size="sm">
            <Form.Control
              as="select"
              name="filterType"
              value={filterType}
              onChange={handleFilterChange}
            >
              <option value="20">All</option>
              <option value="1">Noun</option>
              <option value="2">Verb</option>
              <option value="3">Particle</option>
              <option value="4">Adverb</option>
              <option value="5">Adjective</option>
              <option value="6">Expressions</option>
            </Form.Control>
          </InputGroup>
        </div>
      </div>
      <div className="row justify-content-center">
        <div className="col-lg-2 col-md-3 col-sm-4 text-center">
          <Button type="submit" variant="outline" size="md">
            <Icon name="searchSolid" size="sm" />
            <span className="ml-2">Search</span>
          </Button>
        </div>
      </div>
    </Form>
  );
};

export default SearchBarWords;
