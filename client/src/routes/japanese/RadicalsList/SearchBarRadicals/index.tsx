import { type ChangeEvent, type FormEvent, useState } from 'react';

import type { RadicalListFilters } from '@/api/radicals/hooks/useInfiniteRadicals';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

interface SearchBarRadicalsProps {
  onSearch: (filters: RadicalListFilters) => void;
}

const SearchBarRadicals: React.FC<SearchBarRadicalsProps> = ({ onSearch }) => {
  const [keyword, setKeyword] = useState<string>('');

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    onSearch(keyword.trim() ? { keyword: keyword.trim() } : {});
  };

  const handleKeywordChange = (e: ChangeEvent<HTMLInputElement>) => {
    setKeyword(e.target.value);
  };

  return (
    <form onSubmit={handleSubmit} className="col-lg-12">
      <div className="justify-content-center">
        <div className="col-lg-8 col-md-10 mx-auto">
          <label htmlFor="radical-keyword">Keyword:</label>
          <div className="input-group input-group-sm">
            <input
              id="radical-keyword"
              type="text"
              placeholder="Search"
              aria-label="Search"
              name="keyword"
              value={keyword}
              onChange={handleKeywordChange}
              className="form-control form-control-sm"
            />
            <div className="input-group-text">
              <Button type="submit" variant="outline" size="md">
                <Icon name="searchSolid" size="sm" />
                <span className="ml-2">Search</span>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </form>
  );
};

export default SearchBarRadicals;
