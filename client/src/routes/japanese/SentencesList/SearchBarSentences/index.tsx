import { Button } from "@/components/shared/Button";
import { Icon } from "@/components/shared/Icon";
import React, { FormEvent } from "react";
import { Form, InputGroup } from "react-bootstrap";

interface SearchQuery {
  keyword: string;
}

interface SearchBarSentencesProps {
  fetchQuery: (query: SearchQuery) => void;
}

const SearchBarSentences: React.FC<SearchBarSentencesProps> = ({
  fetchQuery,
}) => {
  const [keyword, setKeyword] = React.useState<string>("");

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
      <div className="row justify-content-center">
        <div className="col-lg-8 col-md-10 mx-auto">
          <Form.Group controlId="formKeyword">
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
              <InputGroup.Text>
                <Button type="submit" variant="outline" size="md">
                  <Icon name="searchSolid" size="sm" />
                  <span className="ml-2">Search</span>
                </Button>
              </InputGroup.Text>
            </InputGroup>
          </Form.Group>
        </div>
      </div>
    </Form>
  );
};

export default SearchBarSentences;
