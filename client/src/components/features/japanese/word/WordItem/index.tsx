import React from 'react';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';

interface WordItemProps {
  id: string | number;
  word: string;
  furigana: string;
  word_type: string;
  meaning: string;
  jlpt: string;
  addToList: (id: string | number) => void;
}

const WordItem: React.FC<WordItemProps> = ({
  id,
  word,
  furigana,
  word_type,
  meaning,
  jlpt,
  addToList,
}) => {
  const handleAddToList = () => {
    addToList(id);
  };

  return (
    <div className="post-preview">
      <ruby className="h2 mr-2">
        {word}
        <rp>(</rp>
        <rt>{furigana}</rt>
        <rp>)</rp>
      </ruby>
      <div className="row">
        <div className="col-md-6">
          <p>type: {word_type}</p>
        </div>
        <div className="col-md-6">
          <p>
            meaning: {meaning},<br /> jlpt: {jlpt}
            <span className="float-right">
              <Link className="tag-link" to={`/word/${id}`}>
                <Icon size="sm" name="externalLink" />
              </Link>
              <Button size="sm" variant="ghost" onClick={handleAddToList}>
                Add to List
              </Button>
            </span>
          </p>
        </div>
      </div>
      <hr />
    </div>
  );
};

export default WordItem;
