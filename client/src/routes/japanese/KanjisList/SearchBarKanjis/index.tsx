import React, { FormEvent } from 'react';
import { Form, InputGroup } from 'react-bootstrap';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

interface SearchQuery {
  keyword: string;
  filterType: string;
}

interface SearchBarKanjisProps {
  fetchQuery: (query: SearchQuery) => void;
}

const SearchBarKanjis: React.FC<SearchBarKanjisProps> = ({ fetchQuery }) => {
  const [keyword, setKeyword] = React.useState<string>('');
  const [filterType, setFilterType] = React.useState<string>('20');

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    fetchQuery({ keyword, filterType });
  };

  const handleKeywordChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setKeyword(e.target.value);
  };

  const handleFilterChange = (e: React.ChangeEvent<HTMLElement>) => {
    const target = e.target as HTMLSelectElement;
    setFilterType(target.value);
  };

  return (
    <Form onSubmit={handleSubmit} className="col-lg-12">
      <div className="row justify-content-center">
        <div className="col-lg-4 col-md-5 col-sm-12 mb-2">
          <Form.Label>Keyword:</Form.Label>
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
          <Form.Label>JLPT:</Form.Label>
          <InputGroup size="sm">
            <Form.Control
              as="select"
              name="filterType"
              value={filterType}
              onChange={handleFilterChange}
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

export default SearchBarKanjis;
