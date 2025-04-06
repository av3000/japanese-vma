import React from "react";
import { Link } from "../shared/Link";

const KanjiItem = ({
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
}) => (
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
      </div>
    </div>
    <hr />
  </div>
);
export default KanjiItem;
