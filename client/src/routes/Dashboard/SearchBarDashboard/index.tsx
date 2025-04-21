import React, { ChangeEvent, useEffect } from 'react';

interface SearchFilters {
  keyword: string;
  sortByWhat: string;
  filterType: number | string;
}

interface SearchbarProps {
  filterResults: (data: SearchFilters) => void;
  searchType?: 'articles' | 'lists' | string;
}

const Searchbar: React.FC<SearchbarProps> = ({ filterResults, searchType }) => {
  const [filters, setFilters] = React.useState<SearchFilters>({
    keyword: '',
    sortByWhat: 'new',
    filterType: 20,
  });

  const sendFilters = () => {
    const timeoutId = setTimeout(() => {
      filterResults(filters);
    }, 300);

    // Clean up timeout if component unmounts or filters change again
    return () => clearTimeout(timeoutId);
  };

  // Call sendFilters whenever filters change
  useEffect(() => {
    const cleanup = sendFilters();
    return cleanup;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filters]);

  const handleChange = (e: ChangeEvent<HTMLSelectElement | HTMLInputElement>) => {
    const { name, value } = e.target;
    setFilters((prevFilters) => ({
      ...prevFilters,
      [name]: value,
    }));
  };

  return (
    <div className="container">
      <form noValidate>
        <div className="row justify-content-end">
          <div className="col-lg-4 col-md-6 col-sm-12 mt-3">
            <input
              onChange={handleChange}
              className="form-control form-control-sm"
              name="keyword"
              type="text"
              placeholder="Ex.: title, text, #tag"
              value={filters.keyword}
              aria-label="Search"
            />
          </div>
          <div className="col-lg-4 col-md-4 col-sm-12 mt-3">
            {searchType === 'articles' && (
              <select
                name="filterType"
                value={filters.filterType}
                className="form-control form-control-sm"
                onChange={handleChange}
              >
                <option value="20">All</option>
                <option value="1">N1</option>
                <option value="2">N2</option>
                <option value="3">N3</option>
                <option value="4">N4</option>
                <option value="5">N5</option>
                <option value="6">Uncommon</option>
              </select>
            )}
            {searchType === 'lists' && (
              <select
                name="filterType"
                value={filters.filterType}
                className="form-control form-control-sm"
                onChange={handleChange}
              >
                <option value="20">All</option>
                <option value="5">Radicals</option>
                <option value="6">Kanjis</option>
                <option value="7">Words</option>
                <option value="8">Sentences</option>
                <option value="9">Articles</option>
              </select>
            )}
          </div>
          <div className="col-lg-4 col-md-2 col-sm-12 mt-3">
            <select
              name="sortByWhat"
              value={filters.sortByWhat}
              className="form-control form-control-sm"
              onChange={handleChange}
            >
              <option value="new">Newest</option>
              <option value="pop">Popular</option>
            </select>
          </div>
        </div>
      </form>
    </div>
  );
};

export default Searchbar;
