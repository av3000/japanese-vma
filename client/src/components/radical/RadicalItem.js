import React, { Component } from 'react';
import { Link } from 'react-router-dom';

const RadicalItem = ({ 
    id,
    radical,
    strokes,
    meaning,
    hiragana,
    addToList
}) => (
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
                {/* <i onClick={addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
                <Link className="tag-link" to={`/radical/${id}`}>
                <i className="fas fa-external-link-alt fa-lg"></i>
                </Link>
                </span>
            </p>
        </div>
        <hr/>
    </div>
 )
export default RadicalItem;
