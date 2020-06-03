import React, { Component } from 'react';
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
        <div className="post-title">
            <h1>{word}</h1>
        </div>
        <div className="post-subtitle">
            <h3>{furigana}</h3>
        </div>
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
