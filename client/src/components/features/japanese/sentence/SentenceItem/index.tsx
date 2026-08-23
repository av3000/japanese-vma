import React from 'react';

import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';

interface SentenceItemProps {
  detailIdentifier: string;
  sentence: string;
  tatoeba_entry?: string | number;
  userId?: string | number;
}

const SentenceItem: React.FC<SentenceItemProps> = ({
  detailIdentifier,
  sentence,
  tatoeba_entry,
  userId,
}) => {
  return (
    <div className="post-preview">
      <div className="post-subtitle">
        <h3>{sentence}</h3>
      </div>
      <div className="row">
        <div className="col-md-6">
          {userId ? (
            <p>UserAuthor - {userId}</p>
          ) : (
            <p>
              Tatoeba entry -{' '}
              <a
                href={`https://tatoeba.org/eng/sentences/show/${tatoeba_entry}`}
                target="_blank"
                rel="noopener noreferrer"
              >
                {tatoeba_entry}
              </a>
            </p>
          )}
        </div>
        <div className="col-md-6">
          <p>
            <span className="float-right">
              <Link className="tag-link" to={`/sentence/${detailIdentifier}`}>
                <Icon size="sm" name="externalLink" />
              </Link>
            </span>
          </p>
        </div>
      </div>
      <hr />
    </div>
  );
};

export default SentenceItem;
