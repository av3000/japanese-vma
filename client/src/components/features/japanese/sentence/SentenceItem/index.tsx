import React from "react";
import { Button } from "@/components/shared/Button";
import { Icon } from "@/components/shared/Icon";
import { Link } from "@/components/shared/Link";

interface SentenceItemProps {
  id: string | number;
  sentence: string;
  tatoeba_entry?: string | number;
  userId?: string | number;
  addToList: (id: string | number) => void;
}

const SentenceItem: React.FC<SentenceItemProps> = ({
  id,
  sentence,
  tatoeba_entry,
  userId,
  addToList,
}) => {
  const handleAddToList = () => {
    addToList(id);
  };

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
              Tatoeba entry -{" "}
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
              <Link className="tag-link" to={`/sentence/${id}`}>
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

export default SentenceItem;
