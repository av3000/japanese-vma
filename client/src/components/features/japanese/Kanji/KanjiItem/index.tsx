import { Button } from "@/components/shared/Button";
import { Link } from "@/components/shared/Link";
import React from "react";

interface KanjiItemProps {
  id: string | number;
  kanji: string;
  stroke_count: number;
  onyomi: string;
  kunyomi: string;
  meaning: string;
  frequency: number | string;
  jlpt: string;
  parts: string;
  addToList: (id: string | number) => void;
}

const KanjiItem: React.FC<KanjiItemProps> = ({
  id,
  kanji,
  stroke_count,
  onyomi,
  kunyomi,
  meaning,
  frequency,
  jlpt,
  parts,
  addToList,
}) => {
  const handleAddToList = () => {
    addToList(id);
  };
  return (
    <div className="post-preview">
      <div className="post-title">
        <h1>{kanji}</h1>
      </div>
      <div className="post-subtitle">
        <h3>{meaning}</h3>
      </div>
      <div className="row">
        <div className="col-md-6">
          <p>
            onyomi: {onyomi}, <br /> kunyomi: {kunyomi}
          </p>
        </div>
        <div className="col-md-3">
          <p>
            frequency: {frequency}, <br /> jlpt: {jlpt}
          </p>
        </div>
        <div className="col-md-3">
          <p>
            parts: {parts}, <br /> stroke_count: {stroke_count}
            <span className="float-right">
              <Link to={`/kanji/${id}`}>Open</Link>
            </span>
          </p>
          <Button size="sm" variant="ghost" onClick={handleAddToList}>
            Add to List
          </Button>
        </div>
      </div>
      <hr />
    </div>
  );
};

export default KanjiItem;
