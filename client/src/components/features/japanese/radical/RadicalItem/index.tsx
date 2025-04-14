import React from 'react';

import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';

interface RadicalItemProps {
  id: string | number;
  radical: string;
  strokes: number;
  meaning: string;
  hiragana: string;
  addToList: (id: string | number) => void;
}

const RadicalItem: React.FC<RadicalItemProps> = ({
  id,
  radical,
  strokes,
  meaning,
  hiragana,
  addToList,
}) => {
  const handleAddToList = () => {
    addToList(id);
  };

  return (
    <div className="post-preview">
      <div className="post-title">
        <h1>{radical}</h1>
      </div>
      <div className="post-subtitle">
        <h3>{hiragana}</h3>
      </div>
      <div className="post-meta">
        <p>
          meaning: {meaning}, strokes: {strokes}
          <span className="float-right">
            <Link className="tag-link" to={`/radical/${id}`}>
              <Icon size="sm" name="externalLink" />
            </Link>
            <Button size="sm" variant="ghost" onClick={handleAddToList}>
              Add to List
            </Button>
          </span>
        </p>
      </div>
      <hr />
    </div>
  );
};

export default RadicalItem;
