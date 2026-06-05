import React, { FormEvent } from 'react';
import { Form, InputGroup } from 'react-bootstrap';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';

interface SearchBarSentencesProps {
	defaultKeyword?: string;
	onSearch: (keyword: string) => void;
}

const SearchBarSentences: React.FC<SearchBarSentencesProps> = ({
	defaultKeyword = '',
	onSearch,
}) => {
	const [keyword, setKeyword] = React.useState<string>(defaultKeyword);

	const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
		event.preventDefault();
		onSearch(keyword.trim());
	};

	const handleKeywordChange = (event: React.ChangeEvent<HTMLInputElement>) => {
		setKeyword(event.target.value);
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
