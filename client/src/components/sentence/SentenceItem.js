import React, { Component } from 'react';
import { Link } from 'react-router-dom';

const SentenceItem = ({ 
    id,
    sentence,
    tatoeba_entry,
    userId,
    addToList
}) => (
    <div className="post-preview">
        <div className="post-subtitle">
            <h3>{sentence}</h3>
        </div>
        <div className="row">
            <div className="col-md-6">
                {userId ? (
                    <p>
                        UserAuthor - {userId}
                    </p>
                ) : (
                    <p>
                        Tatoeba entry - <a href={`https://tatoeba.org/eng/sentences/show/${tatoeba_entry}`}>{tatoeba_entry}</a>
                    </p>
                )}
            </div>
            <div className="col-md-6">
            <p>
                <span className="float-right">
                {/* <i onClick={addToList} className="far fa-bookmark ml-3 fa-lg mr-2"></i> */}
                <Link className="tag-link" to={`/sentence/${id}`}>
                <i className="fas fa-external-link-alt fa-lg"></i>
                </Link>
                </span>
            </p>
            </div>
        </div>
        <hr/>
    </div>
 )
export default SentenceItem;
