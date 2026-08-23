import React from 'react';

import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';

interface RadicalItemProps {
  entityId: number;
  detailIdentifier: string;
  radical: string | null;
  strokes: number | null;
  meaning: string | null;
  hiragana: string | null;
}

const RadicalItem: React.FC<RadicalItemProps> = ({
  detailIdentifier,
  radical,
  strokes,
  meaning,
  hiragana,
}) => {
  return (
    <div className="post-preview">
      <div className="post-title">
        <h1>{radical ?? ''}</h1>
      </div>
      <div className="post-subtitle">
        <h3>{hiragana ?? ''}</h3>
      </div>
      <div className="post-meta">
        <p>
          meaning: {meaning ?? ''}, strokes: {strokes ?? ''}
          <span className="float-right">
            <Link className="tag-link" to={`/radical/${detailIdentifier}`}>
              <Icon size="sm" name="externalLink" />
            </Link>
          </span>
        </p>
      </div>
      <hr />
    </div>
  );
};

export default RadicalItem;
