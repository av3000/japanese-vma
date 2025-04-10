import React, { useState, FormEvent } from "react";
import { Form, InputGroup } from "react-bootstrap";
import { Icon } from "@/components/shared/Icon";
import { Button } from "@/components/shared/Button";

interface SearchQuery {
  keyword: string;
}

interface SearchBarRadicalsProps {
  fetchQuery: (query: SearchQuery) => void;
}

const SearchBarRadicals: React.FC<SearchBarRadicalsProps> = ({
  fetchQuery,
}) => {
  const [keyword, setKeyword] = useState<string>("");

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    fetchQuery({ keyword });
  };

  const handleKeywordChange = (e: React.ChangeEvent<HTMLElement>) => {
    const target = e.target as HTMLInputElement;
    setKeyword(target.value);
  };

  return (
    <Form onSubmit={handleSubmit} className="col-lg-12">
      <div className="justify-content-center">
        <div className="col-lg-8 col-md-10 mx-auto">
          <Form.Label>Keyword:</Form.Label>
          <InputGroup size="sm">
            <Form.Control
              type="text"
              placeholder="Search"
              aria-label="Search"
              name="keyword"
              value={keyword}
              onChange={handleKeywordChange}
              className="form-control form-control-sm"
            />
            <InputGroup.Text>
              <Button type="submit" variant="outline" size="md">
                <Icon name="searchSolid" size="sm" />
                <span className="ml-2">Search</span>
              </Button>
            </InputGroup.Text>
          </InputGroup>
        </div>
      </div>
    </Form>
  );
};

export default SearchBarRadicals;
