import React from 'react';
import { Link } from 'react-router-dom';

const WordItem = ({ 
    id,
    word,
    furigana,
    word_type,
    meaning,
    jlpt,
    addToList
}) => (
    <div className="post-preview">
        <ruby className="h2 mr-2">
            {word}<rp>(</rp><rt>{furigana}</rt><rp>)</rp>
        </ruby>
        <div className="row">
            <div className="col-md-6">
            <p>
                type: {word_type}
            </p>
            </div>
            <div className="col-md-6">
            <p>
                meaning: {meaning},<br/> jlpt: {jlpt}
                <span className="float-right">
                {/* <i onClick={addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
                <Link className="tag-link" to={`/word/${id}`}>
                <i className="fas fa-external-link-alt fa-lg"></i>
                </Link>
                </span>
            </p>
            </div>
        </div>
        <hr/>
    </div>
 )
export default WordItem;
